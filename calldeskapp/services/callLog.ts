import { PermissionsAndroid, Platform } from 'react-native';
import { apiCallJson } from './api';

/**
 * Robustly load the native CallLog module.
 * We use dynamic require to prevent Expo Go from crashing on startup.
 */
const getCallLogModule = () => {
    if (Platform.OS !== 'android') return null;
    try {
        return require('react-native-call-log');
    } catch (e) {
        return null;
    }
};

export const checkCallLogPermission = async () => {
    if (Platform.OS !== 'android') return false;
    return await PermissionsAndroid.check(PermissionsAndroid.PERMISSIONS.READ_CALL_LOG);
};

export const requestCallLogPermission = async () => {
    if (Platform.OS === 'android') {
        try {
            const permissions: any[] = [
                PermissionsAndroid.PERMISSIONS.READ_CALL_LOG,
                PermissionsAndroid.PERMISSIONS.READ_CONTACTS,
            ];

            const granted = await PermissionsAndroid.requestMultiple(permissions);

            return granted[PermissionsAndroid.PERMISSIONS.READ_CALL_LOG] === PermissionsAndroid.RESULTS.GRANTED;
        } catch (err) {
            console.error('Permission request error:', err);
            return false;
        }
    }
    return false;
};

export const fetchAndSyncCallLogs = async () => {
    const CallLog = getCallLogModule();

    // If we are on Android but the module is missing (Expo Go)
    if (Platform.OS === 'android' && !CallLog) {
        console.log('Running in Expo Go: Using Mock Sync for testing');
        return await simulateMockSync();
    }

    // Real native logic
    const hasPermission = await requestCallLogPermission();
    if (!hasPermission) return { success: false, message: 'Permission denied. Please allow Call Log access in settings.' };

    try {
        const logs = await CallLog.load(50);
        if (!logs || !Array.isArray(logs)) return { success: false, message: 'No logs found or failed to load' };

        const formattedLogs = logs
            .filter((log: any) => log && log.phoneNumber)
            .map((log: any) => ({
                mobile: log.phoneNumber.replace(/[^0-9]/g, '').slice(-10),
                name: log.name || '',
                type: log.type === 'INCOMING' ? 'Incoming' : (log.type === 'OUTGOING' ? 'Outgoing' : 'Missed'),
                duration: parseInt(log.duration || '0'),
                call_time: (() => {
                    const timestamp = parseInt(log.timestamp);
                    if (isNaN(timestamp)) return new Date().toISOString().slice(0, 19).replace('T', ' ');
                    const d = new Date(timestamp);
                    const pad = (n: number) => n.toString().padStart(2, '0');
                    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
                })(),
            }));

        if (formattedLogs.length === 0) {
            return { success: true, message: 'No new logs to sync', synced: 0 };
        }

        // Reverse the array so we insert oldest first (higher IDs for newer calls)
        const sortedLogs = formattedLogs.reverse();

        return await apiCallJson('sync_calls.php', sortedLogs);
    } catch (error) {
        return { success: false, message: 'Native sync failed' };
    }
};

/**
 * Simulates a sync for testing purposes in Expo Go / Development
 */
const simulateMockSync = async () => {
    const mockLogs = [
        {
            mobile: '9876543210',
            name: 'Demo Contact',
            type: 'Outgoing',
            duration: 125,
            call_time: new Date().toISOString().slice(0, 19).replace('T', ' '),
        }
    ];

    return await apiCallJson('sync_calls.php', mockLogs);
};
