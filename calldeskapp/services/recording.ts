import * as FileSystem from 'expo-file-system/legacy';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { apiCall } from './api';
import { Platform } from 'react-native';

const RECORDING_PATH_KEY = 'miui_recording_path';
const UPLOADED_FILES_KEY = 'uploaded_recordings';

export const saveRecordingPath = async (path: string) => {
    await AsyncStorage.setItem(RECORDING_PATH_KEY, path);
};

export const getRecordingPath = async () => {
    return await AsyncStorage.getItem(RECORDING_PATH_KEY);
};

export const getUploadedFiles = async (): Promise<string[]> => {
    const data = await AsyncStorage.getItem(UPLOADED_FILES_KEY);
    return data ? JSON.parse(data) : [];
};

export const markFileAsUploaded = async (filename: string) => {
    const uploaded = await getUploadedFiles();
    if (!uploaded.includes(filename)) {
        uploaded.push(filename);
        await AsyncStorage.setItem(UPLOADED_FILES_KEY, JSON.stringify(uploaded));
    }
};

export const resetUploadedFiles = async () => {
    await AsyncStorage.removeItem(UPLOADED_FILES_KEY);
};

/**
 * Parses MIUI filename to extract mobile and call time
 * Example: "Name(9876543210)_20230520153045.mp3"
 */
export const parseMIUIFilename = (filename: string) => {
    // Regular expression to match common MIUI patterns
    // 1. Name(Number)_Timestamp
    // 2. Number_Timestamp
    const phoneRegex = /(\d{10,})/;
    const timeRegex = /(\d{14})/; // YYYYMMDDHHMMSS

    const phoneMatch = filename.match(phoneRegex);
    const timeMatch = filename.match(timeRegex);

    if (phoneMatch && timeMatch) {
        const mobile = phoneMatch[0].slice(-10);
        const t = timeMatch[0];
        // Format to YYYY-MM-DD HH:MM:SS
        const callTime = `${t.slice(0, 4)}-${t.slice(4, 6)}-${t.slice(6, 8)} ${t.slice(8, 10)}:${t.slice(10, 12)}:${t.slice(12, 14)}`;
        return { mobile, callTime };
    }
    return null;
};

export const syncRecordings = async (onProgress?: (msg: string) => void) => {
    if (Platform.OS !== 'android') return { success: false, message: 'Only supported on Android' };

    const path = await getRecordingPath();
    if (!path) return { success: false, message: 'Recording path not set' };

    try {
        onProgress?.('Scanning directory...');
        // Expo FileSystem requires 'file://' prefix for local absolute paths
        const normalizedPath = path.startsWith('file://') ? path : `file://${path}`;
        const files = await FileSystem.readDirectoryAsync(normalizedPath);
        const uploaded = await getUploadedFiles();
        
        const toUpload = files.filter(f => 
            (f.endsWith('.mp3') || f.endsWith('.amr') || f.endsWith('.aac')) && 
            !uploaded.includes(f)
        );

        if (toUpload.length === 0) {
            return { success: true, message: 'All recordings are already synced', count: 0 };
        }

        let syncedCount = 0;
        for (const file of toUpload) {
            const metadata = parseMIUIFilename(file);
            if (!metadata) {
                console.log(`Skipping file (unrecognized format): ${file}`);
                continue;
            }

            onProgress?.(`Uploading ${file}...`);
            const normalizedPath = path.startsWith('file://') ? path : `file://${path}`;
            const fileUri = `${normalizedPath}/${file}`;
            
            // Upload using multipart/form-data
            const result = await uploadFile(fileUri, metadata);
            if (result.success) {
                await markFileAsUploaded(file);
                syncedCount++;
            }
        }

        return { success: true, message: `Successfully synced ${syncedCount} recordings`, count: syncedCount };
    } catch (error: any) {
        console.error('Sync Error:', error);
        return { success: false, message: 'Failed to access recordings: ' + error.message };
    }
};

const uploadFile = async (uri: string, metadata: { mobile: string, callTime: string }) => {
    const formData = new FormData();
    
    // @ts-ignore
    formData.append('recording', {
        uri: Platform.OS === 'android' ? uri : uri.replace('file://', ''),
        name: uri.split('/').pop(),
        type: 'audio/mpeg', // Modern MIUI uses mp3
    });
    
    formData.append('mobile', metadata.mobile);
    formData.append('call_time', metadata.callTime);

    // We can't use the standard apiCall here because it uses URLSearchParams
    // We need a direct fetch for FormData
    const { BASE_URL, TOKEN_KEY } = require('../constants/Config');
    const SecureStore = require('expo-secure-store');
    const token = await SecureStore.getItemAsync(TOKEN_KEY);

    try {
        const response = await fetch(`${BASE_URL}/upload_recording.php?token=${token}`, {
            method: 'POST',
            body: formData,
            headers: {
                'Content-Type': 'multipart/form-data',
                // Authorization header sometimes fails with multipart on some PHP servers, 
                // so we pass token in URL too
                'Authorization': `Bearer ${token}`,
            },
        });

        const text = await response.text();
        return JSON.parse(text);
    } catch (error) {
        console.error('File Upload Error:', error);
        return { success: false };
    }
};
