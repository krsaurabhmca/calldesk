import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, Alert, ActivityIndicator, Platform, TextInput } from 'react-native';
import { Folder, Save, RefreshCw, CheckCircle2, AlertCircle, ChevronLeft, Mic, Keyboard } from 'lucide-react-native';
import * as DocumentPicker from 'expo-document-picker';
import { useRouter } from 'expo-router';
import { getRecordingPath, saveRecordingPath, syncRecordings } from '../../services/recording';

const SUGGESTED_MIUI_PATH = '/storage/emulated/0/MIUI/sound_recorder/call_rec';

export default function RecordingSettings() {
    const [path, setPath] = useState<string>('');
    const [isSyncing, setIsSyncing] = useState(false);
    const [statusMsg, setStatusMsg] = useState('');
    const router = useRouter();

    useEffect(() => {
        loadSettings();
    }, []);

    const loadSettings = async () => {
        const savedPath = await getRecordingPath();
        if (savedPath) setPath(savedPath);
    };

    const handleSavePath = async () => {
        if (!path.trim()) {
            Alert.alert('Error', 'Please enter a valid path');
            return;
        }
        await saveRecordingPath(path.trim());
        Alert.alert('Success', 'Recording path saved');
    };

    const handleUsePreset = () => {
        setPath(SUGGESTED_MIUI_PATH);
    };

    const handleBrowse = async () => {
        try {
            const result = await DocumentPicker.getDocumentAsync({
                type: 'audio/*',
                copyToCacheDirectory: false,
            });

            if (!result.canceled && result.assets && result.assets.length > 0) {
                // On modern Android, the URI is content://...
                // We show an alert explaining how to find the path
                Alert.alert(
                    'Manual Path Required',
                    'Due to Android security, we cannot detect the folder automatically from a file selection.\n\nMost MIUI phones use:\n' + SUGGESTED_MIUI_PATH,
                    [
                        { text: 'Use MIUI Default', onPress: handleUsePreset },
                        { text: 'OK' }
                    ]
                );
            }
        } catch (err) {
            Alert.alert('Error', 'Failed to open file picker');
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
                Alert.alert('Sync Failed', result.message + '\n\nPlease check if the path is correct and permissions are granted.');
            }
        } catch (err) {
            setIsSyncing(false);
            setStatusMsg('');
            Alert.alert('Error', 'Sync failed. Error: ' + err);
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
                <Text style={styles.sectionLabel}>MIUI Recording Folder Path</Text>
                <View style={styles.pathCard}>
                    <TextInput 
                        style={styles.input}
                        value={path}
                        onChangeText={setPath}
                        placeholder="/storage/emulated/0/MIUI/sound_recorder/call_rec"
                        autoCapitalize="none"
                        autoCorrect={false}
                    />
                    
                    <View style={styles.buttonRow}>
                        <TouchableOpacity style={styles.saveBtn} onPress={handleSavePath}>
                            <Save size={18} color="#fff" />
                            <Text style={styles.btnText}>Save Path</Text>
                        </TouchableOpacity>

                        <TouchableOpacity style={styles.presetBtn} onPress={handleUsePreset}>
                            <Mic size={18} color="#6366f1" />
                            <Text style={styles.presetBtnText}>MIUI Default</Text>
                        </TouchableOpacity>
                    </View>
                </View>
                <Text style={styles.infoText}>
                    Note: For MIUI, use the path above. If your recordings are in a different folder, please enter the absolute Android path.
                </Text>
            </View>

            <View style={styles.section}>
                <Text style={styles.sectionLabel}>Sync Controls</Text>
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
    input: {
        fontSize: 14,
        color: '#1e293b',
        fontFamily: Platform.OS === 'ios' ? 'Menlo' : 'monospace',
        backgroundColor: '#f1f5f9',
        padding: 12,
        borderRadius: 8,
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    buttonRow: {
        flexDirection: 'row',
        gap: 12,
    },
    saveBtn: {
        flex: 1,
        backgroundColor: '#6366f1',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 12,
        borderRadius: 12,
        gap: 8,
    },
    btnText: {
        color: '#fff',
        fontWeight: '600',
    },
    presetBtn: {
        flex: 1,
        backgroundColor: '#fff',
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        padding: 12,
        borderRadius: 12,
        gap: 8,
        borderWidth: 1,
        borderColor: '#6366f1',
    },
    presetBtnText: {
        color: '#6366f1',
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
