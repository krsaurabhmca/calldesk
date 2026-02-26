import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, KeyboardAvoidingView, Platform, SafeAreaView, ScrollView } from 'react-native';
import { useRouter } from 'expo-router';
import { signup, registerOrganization } from '../../services/auth';
import { LogIn, Smartphone, Lock, User, UserPlus, Building2, HelpCircle, ShieldCheck, CalendarClock } from 'lucide-react-native';
import { useSnackbar } from '../../context/SnackbarContext';

export default function SignupScreen() {
    const { showSnackbar } = useSnackbar();
    const [orgName, setOrgName] = useState('');
    const [name, setName] = useState('');
    const [mobile, setMobile] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const router = useRouter();

    const handleSignup = async () => {
        if (!orgName || !name || !mobile || !password) {
            showSnackbar('Please fill in all fields', 'error');
            return;
        }

        if (mobile.length !== 10) {
            showSnackbar('Mobile number must be 10 digits', 'error');
            return;
        }

        setLoading(true);
        const result = await registerOrganization(orgName, name, mobile, password);
        setLoading(false);

        if (result.success) {
            showSnackbar('Organization registered successfully', 'success');
            router.replace('/(tabs)');
        } else {
            showSnackbar(result.message || 'Registration failed', 'error');
        }
    };

    return (
        <SafeAreaView style={styles.container}>
            <KeyboardAvoidingView
                behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                style={styles.container}
            >
                <ScrollView
                    contentContainerStyle={{ flexGrow: 1 }}
                    keyboardShouldPersistTaps="handled"
                    showsVerticalScrollIndicator={false}
                >
                    <View style={styles.inner}>
                        <View style={styles.header}>
                            <View style={styles.logoContainer}>
                                <Building2 color="#6366f1" size={40} />
                            </View>
                            <Text style={styles.title}>Register Org</Text>
                            <Text style={styles.subtitle}>Start Your Calldesk Journey</Text>
                            <TouchableOpacity
                                style={styles.helpLink}
                                onPress={() => router.push('/help')}
                            >
                                <HelpCircle size={16} color="#6366f1" />
                                <Text style={styles.helpLinkText}>Learn how it works</Text>
                            </TouchableOpacity>
                        </View>

                        <View style={styles.form}>
                            <View style={styles.inputContainer}>
                                <Building2 color="#64748b" size={20} style={styles.inputIcon} />
                                <TextInput
                                    style={styles.input}
                                    placeholder="Organization Name"
                                    value={orgName}
                                    onChangeText={setOrgName}
                                    placeholderTextColor="#94a3b8"
                                />
                            </View>
                            <View style={styles.inputContainer}>
                                <User color="#64748b" size={20} style={styles.inputIcon} />
                                <TextInput
                                    style={styles.input}
                                    placeholder="Full Name"
                                    value={name}
                                    onChangeText={setName}
                                    placeholderTextColor="#94a3b8"
                                />
                            </View>

                            <View style={styles.inputContainer}>
                                <Smartphone color="#64748b" size={20} style={styles.inputIcon} />
                                <TextInput
                                    style={styles.input}
                                    placeholder="Mobile Number"
                                    value={mobile}
                                    onChangeText={setMobile}
                                    keyboardType="phone-pad"
                                    placeholderTextColor="#94a3b8"
                                    maxLength={10}
                                />
                            </View>

                            <View style={styles.inputContainer}>
                                <Lock color="#64748b" size={20} style={styles.inputIcon} />
                                <TextInput
                                    style={styles.input}
                                    placeholder="Password"
                                    value={password}
                                    onChangeText={setPassword}
                                    secureTextEntry
                                    placeholderTextColor="#94a3b8"
                                />
                            </View>

                            <TouchableOpacity
                                style={[styles.button, loading && styles.buttonDisabled]}
                                onPress={handleSignup}
                                disabled={loading}
                            >
                                <Text style={styles.buttonText}>{loading ? 'Setting up Org...' : 'Register & Join'}</Text>
                                {!loading && <UserPlus color="#fff" size={20} />}
                            </TouchableOpacity>
                        </View>

                        <View style={styles.footer}>
                            <Text style={styles.footerText}>Already have an account?</Text>
                            <TouchableOpacity onPress={() => router.back()}>
                                <Text style={styles.linkText}>Sign In</Text>
                            </TouchableOpacity>
                        </View>

                        <View style={styles.miniHelp}>
                            <Text style={styles.miniHelpTitle}>Why Calldesk?</Text>
                            <View style={styles.miniHelpItem}>
                                <ShieldCheck size={14} color="#10b981" />
                                <Text style={styles.miniHelpText}>Stop lead leakage with auto-sync</Text>
                            </View>
                            <View style={styles.miniHelpItem}>
                                <CalendarClock size={14} color="#f59e0b" />
                                <Text style={styles.miniHelpText}>Smart reminders for follow-ups</Text>
                            </View>
                        </View>
                    </View>
                </ScrollView>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#fff',
    },
    inner: {
        flex: 1,
        padding: 24,
        justifyContent: 'center',
    },
    header: {
        alignItems: 'center',
        marginBottom: 40,
    },
    logoContainer: {
        width: 80,
        height: 80,
        backgroundColor: '#eff6ff',
        borderRadius: 24,
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 20,
    },
    title: {
        fontSize: 28,
        fontWeight: '800',
        color: '#1e293b',
        letterSpacing: -0.5,
    },
    subtitle: {
        fontSize: 16,
        color: '#64748b',
        marginTop: 8,
    },
    form: {
        gap: 16,
    },
    inputContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f8fafc',
        borderWidth: 1,
        borderColor: '#e2e8f0',
        borderRadius: 12,
        paddingHorizontal: 16,
    },
    inputIcon: {
        marginRight: 12,
    },
    input: {
        flex: 1,
        height: 56,
        fontSize: 16,
        color: '#1e293b',
    },
    button: {
        backgroundColor: '#6366f1',
        height: 56,
        borderRadius: 12,
        flexDirection: 'row',
        justifyContent: 'center',
        alignItems: 'center',
        gap: 10,
        marginTop: 12,
        shadowColor: '#6366f1',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 8,
        elevation: 4,
    },
    buttonDisabled: {
        opacity: 0.7,
    },
    buttonText: {
        color: '#fff',
        fontSize: 18,
        fontWeight: '700',
    },
    footer: {
        marginTop: 30,
        alignItems: 'center',
        flexDirection: 'row',
        justifyContent: 'center',
        gap: 8,
    },
    footerText: {
        color: '#94a3b8',
        fontSize: 14,
    },
    linkText: {
        color: '#6366f1',
        fontSize: 14,
        fontWeight: '700',
    },
    helpLink: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        marginTop: 12,
        backgroundColor: '#f5f3ff',
        paddingHorizontal: 12,
        paddingVertical: 6,
        borderRadius: 20,
    },
    helpLinkText: {
        color: '#6366f1',
        fontSize: 13,
        fontWeight: '700',
    },
    miniHelp: {
        marginTop: 40,
        padding: 16,
        backgroundColor: '#f8fafc',
        borderRadius: 16,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    miniHelpTitle: {
        fontSize: 12,
        fontWeight: '800',
        color: '#64748b',
        textTransform: 'uppercase',
        letterSpacing: 1,
        marginBottom: 12,
    },
    miniHelpItem: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        marginBottom: 8,
    },
    miniHelpText: {
        fontSize: 13,
        color: '#475569',
        fontWeight: '600',
    },
});
