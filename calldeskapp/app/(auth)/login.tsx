import React, { useState } from 'react';
import { View, Text, TextInput, TouchableOpacity, StyleSheet, Alert, KeyboardAvoidingView, Platform, Image, SafeAreaView, ScrollView } from 'react-native';
import { useRouter } from 'expo-router';
import { login } from '../../services/auth';
import { LogIn, Smartphone, Lock, HelpCircle } from 'lucide-react-native';
import { useSnackbar } from '../../context/SnackbarContext';

export default function LoginScreen() {
    const { showSnackbar } = useSnackbar();
    const [mobile, setMobile] = useState('');
    const [password, setPassword] = useState('');
    const [loading, setLoading] = useState(false);
    const router = useRouter();

    const handleLogin = async () => {
        if (!mobile || !password) {
            showSnackbar('Please enter mobile and password', 'error');
            return;
        }

        setLoading(true);
        try {
            const result = await login(mobile, password);
            setLoading(false);

            if (result.success) {
                showSnackbar('Login successful', 'success');
                router.replace('/(tabs)');
            } else {
                showSnackbar(result.message || 'Invalid credentials', 'error');
            }
        } catch (error: any) {
            setLoading(false);
            showSnackbar('Connection error: ' + (error.message || 'Unknown'), 'error');
            console.error('Login Error:', error);
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
                                <Smartphone color="#6366f1" size={40} />
                            </View>
                            <Text style={styles.title}>Calldesk CRM</Text>
                            <Text style={styles.subtitle}>Sign in to manage your prospects</Text>
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
                                onPress={handleLogin}
                                disabled={loading}
                            >
                                <Text style={styles.buttonText}>{loading ? 'Signing in...' : 'Sign In'}</Text>
                                {!loading && <LogIn color="#fff" size={20} />}
                            </TouchableOpacity>
                        </View>

                        <TouchableOpacity
                            style={styles.footer}
                            onPress={() => router.push('/(auth)/signup')}
                        >
                            <Text style={styles.footerText}>
                                Don't have an account? <Text style={{ color: '#6366f1', fontWeight: '700' }}>Register Organization</Text>
                            </Text>
                        </TouchableOpacity>
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
        marginTop: 40,
        alignItems: 'center',
    },
    footerText: {
        color: '#94a3b8',
        fontSize: 14,
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
});
