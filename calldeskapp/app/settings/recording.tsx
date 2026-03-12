import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, Alert, ActivityIndicator, Platform } from 'react-native';
import { Folder, Save, RefreshCw, CheckCircle2, AlertCircle, ChevronLeft, Mic } from 'lucide-react-native';
import * as DocumentPicker from 'expo-document-picker';
import { useRouter } from 'expo-router';
import { getRecordingPath, saveRecordingPath, syncRecordings } from '../../services/recording';

export default function RecordingSettings() {
    const [path, setPath] = useState<string | null>(null);
    const [isSyncing, setIsSyncing] = useState(false);
    const [statusMsg, setStatusMsg] = useState('');
    const router = useRouter();

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        const savedPath = await getRecordingPath();
        setPath(savedPath);
    };

    const handleBrowse = async () => {
        try {
            // DocumentPicker in Expo can't easily pick "directories" on all versions,
            // but we can ask user to pick ONE recording file and we'll extract the directory.
            const result = await DocumentPicker.getDocumentAsync({
                type: 'audio/*',
                copyToCacheDirectory: false,
            });

            if (!result.canceled && result.assets && result.assets.length > 0) {
                const fileUri = result.assets[0].uri;
                // Standard Android URI: content://... or file:///...
                // We want the folder path.
                const parts = fileUri.split('/');
                parts.pop(); // remove filename
                const folderPath = parts.join('/');
                
                setPath(folderPath);
                await saveRecordingPath(folderPath);
                Alert.alert('Path Set', 'Recording folder path has been saved.');
            }
        } catch (err) {
            Alert.alert('Error', 'Failed to pick recording path');
        }
    };

    const handleManualSync = async () => {
        if (!path) {
            Alert.alert('Error', 'Please set recording path first');
            return;
        }

        setIsSyncing(true);
        setStatusMsg('Syncing recordings...');
        
        try {
            const result = await syncRecordings((msg) => setStatusMsg(msg));
            setIsSyncing(false);
            setStatusMsg('');
            
            if (result.success) {
                Alert.alert('Sync Complete', result.message);
            } else {
                Alert.alert('Sync Failed', result.message);
            }
        } catch (err) {
            setIsSyncing(false);
            setStatusMsg('');
            Alert.alert('Error', 'An unexpected error occurred during sync');
        }
    };

    return (
        <ScrollView style={styles.container}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
                    <ChevronLeft size={24} color="#1e293b" />
                </TouchableOpacity>
                <Text style={styles.title}>Call Recording Sync</Text>
            </View>

            <View style={styles.section}>
                <Text style={styles.sectionLabel}>MIUI Recording Folder</Text>
                <View style={styles.pathCard}>
                    <Text style={styles.pathText}>
                        {path || 'Not set. Usually: MIUI/sound_recorder/call_rec'}
                    </Text>
                    <TouchableOpacity style={styles.browseBtn} onPress={handleBrowse}>
                        <Folder size={20} color="#fff" />
                        <Text style={styles.browseBtnText}>Browse & Select Folder</Text>
                    </TouchableOpacity>
                </View>
                <Text style={styles.infoText}>
                    Tip: Select any audio file in your call recording folder to detect the path.
                </Text>
            </View>

            <View style={styles.section}>
                <Text style={styles.sectionLabel}>Actions</Text>
                <TouchableOpacity 
                    style={[styles.syncBtn, isSyncing && styles.disabledBtn]} 
                    onPress={handleManualSync}
                    disabled={isSyncing}
                >
                    {isSyncing ? (
                        <ActivityIndicator color="#fff" />
                    ) : (
                        <RefreshCw size={20} color="#fff" />
                    )}
                    <Text style={styles.syncBtnText}>
                        {isSyncing ? 'Syncing...' : 'Sync All Now'}
                    </Text>
                </TouchableOpacity>
                
                {statusMsg ? <Text style={styles.statusText}>{statusMsg}</Text> : null}
            </View>

            <View style={styles.infoCard}>
                <CheckCircle2 size={20} color="#10b981" />
                <View style={{ flex: 1 }}>
                    <Text style={styles.infoCardTitle}>How it works</Text>
                    <Text style={styles.infoCardText}>
                        Calldesk matches MIUI recordings by filename (Phone Number and Timestamp) 
                        and uploads them to your server automatically in the background.
                    </Text>
                </View>
            </View>
        </ScrollView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f8fafc',
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 20,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
        gap: 16,
    },
    backBtn: {
        padding: 4,
    },
    title: {
        fontSize: 18,
        fontWeight: '700',
        color: '#0f172a',
    },
    section: {
        padding: 20,
        gap: 12,
    },
    sectionLabel: {
        fontSize: 14,
        fontWeight: '600',
        color: '#64748b',
        textTransform: 'uppercase',
        letterSpacing: 0.5,
    },
    pathCard: {
        backgroundColor: '#fff',
        padding: 20,
        borderRadius: 16,
        borderWidth: 1,
        borderColor: '#e2e8f0',
        gap: 16,
    },
    pathText: {
        fontSize: 14,
        color: '#1e293b',
        fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
        backgroundColor: '#f1f5f9',
        padding: 12,
        borderRadius: 8,
    },
    browseBtn: {
        backgroundColor: '#6366f1',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 12,
        borderRadius: 12,
        gap: 8,
    },
    browseBtnText: {
        color: '#fff',
        fontWeight: '600',
    },
    infoText: {
        fontSize: 12,
        color: '#64748b',
        fontStyle: 'italic',
        paddingHorizontal: 4,
    },
    syncBtn: {
        backgroundColor: '#10b981',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 16,
        borderRadius: 12,
        gap: 10,
    },
    disabledBtn: {
        opacity: 0.6,
    },
    syncBtnText: {
        color: '#fff',
        fontSize: 16,
        fontWeight: '700',
    },
    statusText: {
        textAlign: 'center',
        fontSize: 13,
        color: '#6366f1',
        marginTop: 8,
    },
    infoCard: {
        margin: 20,
        padding: 16,
        backgroundColor: '#ecfdf5',
        borderRadius: 12,
        flexDirection: 'row',
        gap: 12,
        borderWidth: 1,
        borderColor: '#d1fae5',
    },
    infoCardTitle: {
        fontSize: 14,
        fontWeight: '700',
        color: '#064e3b',
        marginBottom: 4,
    },
    infoCardText: {
        fontSize: 13,
        color: '#065f46',
        lineHeight: 18,
    },
});
