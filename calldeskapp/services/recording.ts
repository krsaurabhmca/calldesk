import * as FileSystem from 'expo-file-system/legacy';
const { StorageAccessFramework } = FileSystem as any;
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
 * Example 1: "Name(9876543210)_20230520153045.mp3"
 * Example 2: "9876543210_2025-12-03_16-37-13.mp3"
 */
export const parseMIUIFilename = (filename: string) => {
    const decodedName = decodeURIComponent(filename);
    const phoneRegex = /(\d{10,})/;
    
    // Pattern 1: Continuous 14 digits (YYYYMMDDHHMMSS)
    const timeRegex1 = /(\d{14})/; 
    // Pattern 2: Dash/Underscore separated (YYYY-MM-DD_HH-MM-SS)
    const timeRegex2 = /(\d{4})[-_](\d{2})[-_](\d{2})[-_](\d{2})[-_](\d{2})[-_](\d{2})/;

    const phoneMatch = decodedName.match(phoneRegex);
    const timeMatch1 = decodedName.match(timeRegex1);
    const timeMatch2 = decodedName.match(timeRegex2);

    if (phoneMatch) {
        const mobile = phoneMatch[0].slice(-10);
        let callTime = '';

        if (timeMatch1) {
            const t = timeMatch1[0];
            callTime = `${t.slice(0, 4)}-${t.slice(4, 6)}-${t.slice(6, 8)} ${t.slice(8, 10)}:${t.slice(10, 12)}:${t.slice(12, 14)}`;
        } else if (timeMatch2) {
            const [_, y, m, d, h, min, s] = timeMatch2;
            callTime = `${y}-${m}-${d} ${h}:${min}:${s}`;
        }

        if (callTime) {
            return { mobile, callTime, originalName: decodedName };
        }
    }
    return null;
};

export const syncRecordings = async (onProgress?: (msg: string) => void) => {
    if (Platform.OS !== 'android') return { success: false, message: 'Only supported on Android' };

    const path = await getRecordingPath();
    if (!path) return { success: false, message: 'Recording path not set' };

    try {
        const isSAF = path.startsWith('content://');
        onProgress?.(isSAF ? 'Accessing folder (SAF)...' : 'Accessing folder...');
        
        let files: string[] = [];
        if (isSAF) {
            files = await StorageAccessFramework.readDirectoryAsync(path);
        } else {
            const normalizedPath = path.startsWith('file://') ? path : `file://${path}`;
            const folderInfo = await FileSystem.getInfoAsync(normalizedPath);
            if (!folderInfo.exists) {
                return { success: false, message: 'Folder does not exist. Please check the path.' };
            }
            files = await FileSystem.readDirectoryAsync(normalizedPath);
        }

        console.log(`Sync: Found ${files.length} total files`);
        const uploaded = await getUploadedFiles();
        
        // Filter recording files
        const toUpload = files.filter(f => {
            const fullPath = isSAF ? decodeURIComponent(f) : f;
            const fileName = fullPath.split('/').pop() || '';
            const isAudio = (fileName.endsWith('.mp3') || fileName.endsWith('.amr') || fileName.endsWith('.aac') || fileName.endsWith('.m4a'));
            return isAudio && !uploaded.includes(fileName);
        });

        if (toUpload.length === 0) {
            return { success: true, message: 'No new recordings found', count: 0 };
        }

        let syncedCount = 0;
        let failCount = 0;

        for (const fileUri of toUpload) {
            const fullPath = isSAF ? decodeURIComponent(fileUri) : fileUri;
            const fileName = fullPath.split('/').pop() || '';
            const metadata = parseMIUIFilename(fileName);
            
            if (!metadata) {
                console.log(`Sync: Skipping file (unrecognized format): ${fileName}`);
                continue;
            }

            console.log(`Sync: Metadata for ${fileName}:`, metadata);
            onProgress?.(`Uploading ${syncedCount + 1}/${toUpload.length}...`);
            
            let result;
            if (isSAF) {
                result = await uploadFile(fileUri, metadata);
            } else {
                const normalizedPath = path.startsWith('file://') ? path : `file://${path}`;
                const fullUri = `${normalizedPath}/${fileUri}`;
                result = await uploadFile(fullUri, metadata);
            }

            if (result.success) {
                console.log(`Sync: Successfully uploaded ${fileName}`);
                await markFileAsUploaded(fileName);
                syncedCount++;
            } else {
                console.error(`Sync: Failed to upload ${fileName}:`, result.message);
                failCount++;
            }
        }

        return { 
            success: true, 
            message: `Synced ${syncedCount} recordings. ${failCount > 0 ? failCount + ' failed.' : ''}`, 
            count: syncedCount 
        };
    } catch (error: any) {
        console.error('Sync Error:', error);
        return { success: false, message: 'Error: ' + error.message };
    }
};

const uploadFile = async (uri: string, metadata: { mobile: string, callTime: string, originalName?: string }) => {
    const formData = new FormData();
    // Use originalName if provided, otherwise fallback to parsing from URI
    const fileName = metadata.originalName || decodeURIComponent(uri).split('/').pop() || 'recording.mp3';
    
    // @ts-ignore
    formData.append('recording', {
        uri: uri,
        name: fileName,
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
