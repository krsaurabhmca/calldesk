import { useEffect, useRef, useState } from 'react';
import { View, Text, ActivityIndicator, StyleSheet, Animated, Platform } from 'react-native';
import { useRouter } from 'expo-router';
import * as SecureStore from 'expo-secure-store';
import { TOKEN_KEY } from '../constants/Config';
import { isAuthenticated } from '../services/auth';
import { checkCallLogPermission, fetchAndSyncCallLogs } from '../services/callLog';
import { getRecordingPath, syncRecordings } from '../services/recording';

type SyncStatus = 'pending' | 'running' | 'done' | 'skipped' | 'error';

interface SyncStep {
    label: string;
    detail: string;
    status: SyncStatus;
}

const initialSteps: SyncStep[] = [
    { label: 'Authenticating', detail: 'Verifying your session...', status: 'pending' },
    { label: 'Call Logs', detail: 'Waiting...', status: 'pending' },
    { label: 'Recordings', detail: 'Waiting...', status: 'pending' },
];

const statusIcon = (s: SyncStatus) => {
    if (s === 'running') return '⏳';
    if (s === 'done')    return '✅';
    if (s === 'skipped') return '⏭️';
    if (s === 'error')   return '⚠️';
    return '○';
};

export default function Index() {
    const router = useRouter();
    const [steps, setSteps] = useState<SyncStep[]>(initialSteps);
    const [currentStep, setCurrentStep] = useState(0);
    const fadeAnim = useRef(new Animated.Value(1)).current;

    const updateStep = (index: number, update: Partial<SyncStep>) => {
        setSteps(prev => {
            const next = [...prev];
            next[index] = { ...next[index], ...update };
            return next;
        });
        setCurrentStep(index);

        Animated.sequence([
            Animated.timing(fadeAnim, { toValue: 0.6, duration: 100, useNativeDriver: true }),
            Animated.timing(fadeAnim, { toValue: 1, duration: 200, useNativeDriver: true }),
        ]).start();
    };

    useEffect(() => {
        startup();
    }, []);

    const delay = (ms: number) => new Promise<void>(r => setTimeout(r, ms));

    const startup = async () => {
        await delay(300); // Let UI render first

        // ── Step 0: Auth ────────────────────────────────────────────────
        updateStep(0, { status: 'running', detail: 'Checking token...' });
        let token: string | null = null;
        try {
            token = await SecureStore.getItemAsync(TOKEN_KEY);
        } catch (e) {}

        if (!token) {
            updateStep(0, { status: 'skipped', detail: 'Not logged in' });
            await delay(300);
            router.replace('/(auth)/login');
            return;
        }
        updateStep(0, { status: 'done', detail: 'Session valid ✓' });
        await delay(300);

        // ── Step 1: Call Logs ───────────────────────────────────────────
        if (Platform.OS === 'android') {
            updateStep(1, { status: 'running', detail: 'Checking permission...' });
            try {
                const hasPerm = await checkCallLogPermission();
                if (!hasPerm) {
                    updateStep(1, { status: 'skipped', detail: 'Permission not granted yet. Tap "Sync" in Calls tab.' });
                } else {
                    updateStep(1, { status: 'running', detail: 'Reading device call history...' });
                    const result = await fetchAndSyncCallLogs();
                    console.log('Startup call log sync result:', JSON.stringify(result));
                    if (result?.success) {
                        const count = result?.data?.synced ?? result?.synced ?? 0;
                        updateStep(1, { status: 'done', detail: `${count} new call logs uploaded` });
                    } else {
                        updateStep(1, { status: 'error', detail: result?.message || 'Sync failed' });
                    }
                }
            } catch (e: any) {
                console.error('Startup call log error:', e);
                updateStep(1, { status: 'error', detail: e?.message || 'Unknown error' });
            }
        } else {
            updateStep(1, { status: 'skipped', detail: 'Android only' });
        }
        await delay(400);

        // ── Step 2: Recordings ──────────────────────────────────────────
        updateStep(2, { status: 'running', detail: 'Checking recording path...' });
        try {
            const path = await getRecordingPath();
            console.log('Recording path:', path);
            if (!path) {
                updateStep(2, { status: 'skipped', detail: 'No folder set. Go to Settings → Recording.' });
            } else {
                updateStep(2, { status: 'running', detail: 'Scanning folder for new recordings...' });
                const result = await syncRecordings((msg) => {
                    updateStep(2, { status: 'running', detail: msg });
                });
                console.log('Startup recording sync result:', JSON.stringify(result));
                if (result?.success) {
                    const count = result?.count ?? 0;
                    updateStep(2, {
                        status: 'done',
                        detail: count > 0 ? `${count} recording(s) uploaded` : 'All up to date',
                    });
                } else {
                    updateStep(2, { status: 'error', detail: result?.message || 'Sync failed' });
                }
            }
        } catch (e: any) {
            console.error('Startup recording sync error:', e);
            updateStep(2, { status: 'error', detail: e?.message || 'Unknown error' });
        }
        await delay(700);

        // ── Done: Navigate ──────────────────────────────────────────────
        router.replace('/(tabs)');
    };

    return (
        <View style={styles.container}>
            {/* Logo */}
            <View style={styles.logoBlock}>
                <View style={styles.logoIcon}>
                    <Text style={styles.logoEmoji}>📞</Text>
                </View>
                <Text style={styles.appName}>CallDesk</Text>
                <Text style={styles.tagline}>Please wait while we sync your data</Text>
            </View>

            {/* Steps */}
            <View style={styles.card}>
                {steps.map((s, i) => (
                    <View key={i} style={[styles.stepRow, i < steps.length - 1 && styles.stepRowBorder]}>
                        <View style={styles.stepLeft}>
                            <Text style={styles.stepIcon}>{statusIcon(s.status)}</Text>
                        </View>
                        <View style={styles.stepRight}>
                            <View style={styles.stepLabelRow}>
                                <Text style={[
                                    styles.stepLabel,
                                    s.status === 'running' && { color: '#6366f1', fontWeight: '800' },
                                    s.status === 'done'    && { color: '#10b981' },
                                    s.status === 'error'   && { color: '#ef4444' },
                                    s.status === 'skipped' && { color: '#94a3b8' },
                                ]}>
                                    {s.label}
                                </Text>
                                {s.status === 'running' && (
                                    <ActivityIndicator size="small" color="#6366f1" style={{ marginLeft: 8 }} />
                                )}
                            </View>
                            <Text style={[
                                styles.stepDetail,
                                s.status === 'error' && { color: '#ef4444' },
                            ]} numberOfLines={2}>
                                {s.detail}
                            </Text>
                        </View>
                    </View>
                ))}
            </View>

            <Text style={styles.version}>v1.3.0</Text>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f8fafc',
        justifyContent: 'center',
        alignItems: 'center',
        paddingHorizontal: 28,
    },
    logoBlock: {
        alignItems: 'center',
        marginBottom: 36,
    },
    logoIcon: {
        width: 80,
        height: 80,
        borderRadius: 24,
        backgroundColor: '#6366f1',
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 16,
        shadowColor: '#6366f1',
        shadowOffset: { width: 0, height: 8 },
        shadowOpacity: 0.35,
        shadowRadius: 16,
        elevation: 12,
    },
    logoEmoji: { fontSize: 38 },
    appName: {
        fontSize: 30,
        fontWeight: '800',
        color: '#0f172a',
        letterSpacing: -0.5,
    },
    tagline: {
        fontSize: 12,
        color: '#94a3b8',
        fontWeight: '600',
        marginTop: 6,
        textAlign: 'center',
    },
    card: {
        width: '100%',
        backgroundColor: '#fff',
        borderRadius: 20,
        paddingVertical: 8,
        paddingHorizontal: 4,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.06,
        shadowRadius: 16,
        elevation: 4,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    stepRow: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        paddingVertical: 14,
        paddingHorizontal: 16,
    },
    stepRowBorder: {
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    stepLeft: {
        width: 32,
        alignItems: 'center',
        paddingTop: 1,
    },
    stepIcon: {
        fontSize: 16,
    },
    stepRight: {
        flex: 1,
    },
    stepLabelRow: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    stepLabel: {
        fontSize: 14,
        fontWeight: '700',
        color: '#475569',
    },
    stepDetail: {
        fontSize: 11,
        color: '#94a3b8',
        marginTop: 3,
        lineHeight: 16,
    },
    version: {
        marginTop: 24,
        fontSize: 11,
        color: '#cbd5e1',
        fontWeight: '600',
    },
});
