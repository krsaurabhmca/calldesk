import * as TaskManager from 'expo-task-manager';
import * as BackgroundFetch from 'expo-background-fetch';
import { syncRecordings } from './recording';
import { fetchAndSyncCallLogs, checkCallLogPermission } from './callLog';
import { Platform } from 'react-native';

const RECORDING_SYNC_TASK = 'recording-sync-task';

// Define the background task — runs call log + recording sync every 15 min
TaskManager.defineTask(RECORDING_SYNC_TASK, async () => {
    try {
        console.log('Background Sync: Starting...');

        const results = await Promise.allSettled([
            // Sync call logs if permission already granted
            checkCallLogPermission().then(ok => ok ? fetchAndSyncCallLogs() : Promise.resolve(null)),
            // Sync new recordings
            syncRecordings(),
        ]);

        console.log('Background Sync done:', results.map(r => r.status));
        return BackgroundFetch.BackgroundFetchResult.NewData;
    } catch (error) {
        console.error('Background Sync Error:', error);
        return BackgroundFetch.BackgroundFetchResult.Failed;
    }
});

export const registerBackgroundSync = async () => {
    if (Platform.OS === 'web') return;
    
    try {
        console.log('Registering Background Sync Task...');
        const isRegistered = await TaskManager.isTaskRegisteredAsync(RECORDING_SYNC_TASK);
        if (!isRegistered) {
            await BackgroundFetch.registerTaskAsync(RECORDING_SYNC_TASK, {
                minimumInterval: 15 * 60, // 15 minutes
                stopOnTerminate: false, // Continue sync after app is closed
                startOnBoot: true, // Start sync after device restart
            });
            console.log('Background Sync Task Registered successfully');
        } else {
            console.log('Background Sync Task already registered');
        }
    } catch (err) {
        console.error('Task Registration failed:', err);
    }
};

export const unregisterBackgroundSync = async () => {
    if (await TaskManager.isTaskRegisteredAsync(RECORDING_SYNC_TASK)) {
        await BackgroundFetch.unregisterTaskAsync(RECORDING_SYNC_TASK);
    }
};
