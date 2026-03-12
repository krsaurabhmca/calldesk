import { useEffect, useState } from 'react';
import { View, Text, ActivityIndicator, StyleSheet, Animated } from 'react-native';
import { useRouter } from 'expo-router';
import { isAuthenticated } from '../services/auth';
import { fetchAndSyncCallLogs, checkCallLogPermission } from '../services/callLog';
import { syncRecordings, getRecordingPath } from '../services/recording';

const STEPS = [
    'Checking authentication...',
    'Syncing call logs...',
    'Syncing recordings...',
    'All done! Loading app...',
];

export default function Index() {
    const router = useRouter();
    const [step, setStep] = useState(0);
    const [detail, setDetail] = useState('');
    const [fadeAnim] = useState(new Animated.Value(1));

    const animateStep = (newStep: number, detailMsg: string) => {
        Animated.sequence([
            Animated.timing(fadeAnim, { toValue: 0, duration: 150, useNativeDriver: true }),
            Animated.timing(fadeAnim, { toValue: 1, duration: 300, useNativeDriver: true }),
        ]).start();
        setStep(newStep);
        setDetail(detailMsg);
    };

    useEffect(() => {
        startupSequence();
    }, []);

    const startupSequence = async () => {
        // Step 0 — Auth check
        animateStep(0, '');
        let isAuth = false;
        try {
            isAuth = await isAuthenticated();
        } catch (e) {
            router.replace('/(auth)/login');
            return;
        }

        if (!isAuth) {
            router.replace('/(auth)/login');
            return;
        }

        // Step 1 — Sync call logs
        animateStep(1, 'Reading device call history...');
        try {
            const hasPermission = await checkCallLogPermission();
            if (hasPermission) {
                const result = await fetchAndSyncCallLogs();
                if (result.success) {
                    animateStep(1, `✓ ${result?.data?.synced ?? 0} new call logs uploaded`);
                } else {
                    animateStep(1, 'Call logs up to date');
                }
            } else {
                animateStep(1, 'Call log permission not set — skipping');
            }
        } catch (e) {
            animateStep(1, 'Call log sync skipped');
        }

        await delay(600);

        // Step 2 — Sync recordings
        animateStep(2, 'Checking recording folder...');
        try {
            const path = await getRecordingPath();
            if (path) {
                animateStep(2, 'Uploading new recordings...');
                const result = await syncRecordings();
                if (result.success) {
                    const count = result.count ?? 0;
                    animateStep(2, count > 0 ? `✓ ${count} new recording(s) uploaded` : '✓ Recordings up to date');
                } else {
                    animateStep(2, result.message || 'Recording sync skipped');
                }
            } else {
                animateStep(2, 'Recording folder not set — skipping');
            }
        } catch (e: any) {
            animateStep(2, 'Recording sync skipped');
        }

        await delay(700);

        // Step 3 — Done
        animateStep(3, '');
        await delay(400);

        router.replace('/(tabs)');
    };

    const delay = (ms: number) => new Promise(resolve => setTimeout(resolve, ms));

    return (
        <View style={styles.container}>
            {/* Logo / App Name */}
            <View style={styles.logoBlock}>
                <View style={styles.logoIcon}>
                    <Text style={styles.logoEmoji}>📞</Text>
                </View>
                <Text style={styles.appName}>CallDesk</Text>
                <Text style={styles.appTagline}>CRM & Call Sync</Text>
            </View>

            {/* Sync Progress Card */}
            <View style={styles.card}>
                <ActivityIndicator size="small" color="#6366f1" style={{ marginBottom: 16 }} />
                <Animated.View style={{ opacity: fadeAnim }}>
                    <Text style={styles.stepText}>{STEPS[step]}</Text>
                    {detail ? <Text style={styles.detailText}>{detail}</Text> : null}
                </Animated.View>

                {/* Progress dots */}
                <View style={styles.dotsRow}>
                    {STEPS.map((_, i) => (
                        <View
                            key={i}
                            style={[
                                styles.dot,
                                i <= step && styles.dotActive,
                                i === step && styles.dotCurrent,
                            ]}
                        />
                    ))}
                </View>
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
        paddingHorizontal: 32,
    },
    logoBlock: {
        alignItems: 'center',
        marginBottom: 40,
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
        shadowOpacity: 0.3,
        shadowRadius: 16,
        elevation: 12,
    },
    logoEmoji: {
        fontSize: 38,
    },
    appName: {
        fontSize: 32,
        fontWeight: '800',
        color: '#0f172a',
        letterSpacing: -0.5,
    },
    appTagline: {
        fontSize: 13,
        color: '#94a3b8',
        fontWeight: '600',
        marginTop: 4,
    },
    card: {
        width: '100%',
        backgroundColor: '#fff',
        borderRadius: 20,
        padding: 24,
        alignItems: 'center',
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.06,
        shadowRadius: 16,
        elevation: 4,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    stepText: {
        fontSize: 15,
        fontWeight: '700',
        color: '#1e293b',
        textAlign: 'center',
    },
    detailText: {
        fontSize: 12,
        color: '#64748b',
        marginTop: 6,
        textAlign: 'center',
        fontWeight: '500',
    },
    dotsRow: {
        flexDirection: 'row',
        gap: 8,
        marginTop: 20,
    },
    dot: {
        width: 8,
        height: 8,
        borderRadius: 4,
        backgroundColor: '#e2e8f0',
    },
    dotActive: {
        backgroundColor: '#c7d2fe',
    },
    dotCurrent: {
        backgroundColor: '#6366f1',
        width: 24,
    },
    version: {
        marginTop: 24,
        fontSize: 11,
        color: '#cbd5e1',
        fontWeight: '600',
    },
});
