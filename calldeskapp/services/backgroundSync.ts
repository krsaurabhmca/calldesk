import * as TaskManager from 'expo-task-manager';
import * as BackgroundFetch from 'expo-background-fetch';
import { syncRecordings } from './recording';
import { Platform } from 'react-native';

const RECORDING_SYNC_TASK = 'recording-sync-task';

// Define the task
TaskManager.defineTask(RECORDING_SYNC_TASK, async () => {
    try {
        console.log('Background Sync: Starting recording sync...');
        const result = await syncRecordings();
        console.log('Background Sync result:', result);
        
        return result.success 
            ? BackgroundFetch.BackgroundFetchResult.NewData 
            : BackgroundFetch.BackgroundFetchResult.Failed;
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
