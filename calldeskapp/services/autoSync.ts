/**
 * autoSync.ts
 *
 * Runs silently on app open — no UI, no alerts.
 * 1. Syncs call logs from the device's native CallLog API
 * 2. Syncs new MIUI recordings to the server
 *
 * Throttled with a timestamp so it doesn't run more than once per 5 minutes.
 */
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import { Platform } from 'react-native';
import { syncRecordings } from './recording';
import { TOKEN_KEY, BASE_URL } from '../constants/Config';

const LAST_SYNC_KEY   = 'auto_last_sync_ts';
const SYNC_INTERVAL_MS = 5 * 60 * 1000; // 5 minutes minimum between auto-syncs

/** Read the last sync timestamp */
const getLastSync = async (): Promise<number> => {
    const val = await AsyncStorage.getItem(LAST_SYNC_KEY);
    return val ? parseInt(val, 10) : 0;
};

/** Save the current timestamp as last sync */
const setLastSync = async () => {
    await AsyncStorage.setItem(LAST_SYNC_KEY, Date.now().toString());
};

/** Upload call logs from the device using the native CallLog.getAll polyfill */
const syncCallLogs = async (): Promise<void> => {
    if (Platform.OS !== 'android') return;

    try {
        // Use react-native-call-log if available, else skip
        // @ts-ignore
        const CallLog = require('react-native-call-log');
        const token   = await SecureStore.getItemAsync(TOKEN_KEY);
        if (!token) return;

        const logs: any[] = await CallLog.getAll(200); // Fetch last 200 call logs
        if (!logs || logs.length === 0) return;

        const formattedLogs = logs.map((log: any) => ({
            mobile    : log.phoneNumber?.replace(/\D/g, '').slice(-10) || '',
            type      : log.type === '1' ? 'Incoming' : log.type === '2' ? 'Outgoing' : 'Missed',
            duration  : parseInt(log.duration || '0', 10),
            call_time : new Date(parseInt(log.timestamp)).toISOString().replace('T', ' ').substring(0, 19),
            caller_name: log.name || '',
        })).filter((l: any) => l.mobile.length === 10);

        const url = `${BASE_URL}/sync_calls.php?token=${token}`;
        await fetch(url, {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'Authorization': `Bearer ${token}` },
            body   : JSON.stringify({ logs: formattedLogs }),
        });

        console.log(`AutoSync: Uploaded ${formattedLogs.length} call logs`);
    } catch (e: any) {
        // react-native-call-log not installed or permission denied — skip silently
        console.log('AutoSync: Call log sync skipped -', e?.message || e);
    }
};

/** Main auto-sync entry point — call this from _layout.tsx */
export const runAutoSync = async (): Promise<void> => {
    try {
        const token = await SecureStore.getItemAsync(TOKEN_KEY);
        if (!token) return; // Not logged in

        const last = await getLastSync();
        const now  = Date.now();

        if (now - last < SYNC_INTERVAL_MS) {
            console.log(`AutoSync: Skipped (last sync was ${Math.round((now - last) / 1000)}s ago)`);
            return;
        }

        console.log('AutoSync: Starting silent sync...');
        await setLastSync(); // Mark sync started immediately to prevent race conditions

        // Run call-log sync and recording sync in parallel
        await Promise.allSettled([
            syncCallLogs(),
            syncRecordings(),
        ]);

        console.log('AutoSync: Done');
    } catch (err) {
        console.error('AutoSync Error:', err);
    }
};
