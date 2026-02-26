import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Image, Alert, Linking } from 'react-native';
import { useRouter } from 'expo-router';
import { getUser, logout } from '../services/auth';
import { User, LogOut, ChevronRight, Settings, Phone, Mail, Shield, Smartphone, Building2, Trash2, FileText } from 'lucide-react-native';
import { useSnackbar } from '../context/SnackbarContext';

export default function ProfileScreen() {
    const router = useRouter();
    const { showSnackbar } = useSnackbar();
    const [user, setUser] = useState<any>(null);

    useEffect(() => {
        loadUser();
    }, []);

    const loadUser = async () => {
        const userData = await getUser();
        setUser(userData);
    };

    const handleLogout = () => {
        Alert.alert('Logout', 'Are you sure you want to logout?', [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Logout',
                style: 'destructive',
                onPress: async () => {
                    await logout();
                    router.replace('/(auth)/login');
                }
            }
        ]);
    };

    const MenuJson = [
        {
            title: 'Account Settings',
            items: [
                { icon: User, label: 'Edit Profile', action: () => showSnackbar('Edit Profile feature coming soon', 'info') },
                { icon: Shield, label: 'Change Password', action: () => showSnackbar('Change Password feature coming soon', 'info') },
            ]
        },
        {
            title: 'Preferences',
            items: [
                { icon: Settings, label: 'App Settings', action: () => showSnackbar('Settings coming soon', 'info') },
            ]
        },
        {
            title: 'Support',
            items: [
                { icon: Phone, label: 'Contact Support', action: () => showSnackbar('Support contact: support@calldesk.in', 'info') },
                { icon: FileText, label: 'Privacy Policy', action: () => Linking.openURL('https://calldesk.offerplant.com/privacy-policy.php') },
                { icon: Trash2, label: 'Delete Account', action: () => Linking.openURL('https://calldesk.offerplant.com/delete-account.php') },
            ]
        }
    ];

    return (
        <ScrollView style={styles.container} showsVerticalScrollIndicator={false}>
            {/* Header / Profile Card */}
            <View style={styles.header}>
                <View style={styles.profileCard}>
                    <View style={styles.avatar}>
                        <Text style={styles.avatarText}>{user?.name?.charAt(0) || 'U'}</Text>
                    </View>
                    <View style={styles.userInfo}>
                        <Text style={styles.userName}>{user?.name || 'User'}</Text>
                        <Text style={styles.userRole}>{user?.role || 'Executive'}</Text>
                        <View style={styles.mobileRow}>
                            <Building2 size={14} color="#64748b" />
                            <Text style={styles.userMobile}>{user?.organization_name || 'Organization'}</Text>
                        </View>
                        <View style={styles.mobileRow}>
                            <Smartphone size={14} color="#94a3b8" />
                            <Text style={styles.userMobile}>{user?.mobile || 'No mobile'}</Text>
                        </View>
                    </View>
                </View>
            </View>

            {/* Menu Sections */}
            <View style={styles.menuContainer}>
                {MenuJson.map((section, index) => (
                    <View key={index} style={styles.section}>
                        <Text style={styles.sectionTitle}>{section.title}</Text>
                        <View style={styles.sectionContent}>
                            {section.items.map((item, idx) => (
                                <TouchableOpacity
                                    key={idx}
                                    style={[
                                        styles.menuItem,
                                        idx !== section.items.length - 1 && styles.menuItemBorder
                                    ]}
                                    onPress={item.action}
                                >
                                    <View style={styles.menuItemLeft}>
                                        <View style={styles.iconBox}>
                                            <item.icon size={20} color="#6366f1" />
                                        </View>
                                        <Text style={styles.menuItemLabel}>{item.label}</Text>
                                    </View>
                                    <ChevronRight size={18} color="#cbd5e1" />
                                </TouchableOpacity>
                            ))}
                        </View>
                    </View>
                ))}

                {/* Logout Button */}
                <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
                    <LogOut size={20} color="#ef4444" />
                    <Text style={styles.logoutText}>Logout</Text>
                </TouchableOpacity>

                <Text style={styles.versionText}>Version 1.0.0</Text>
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
        padding: 20,
        paddingTop: 40,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    profileCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 16,
    },
    avatar: {
        width: 70,
        height: 70,
        borderRadius: 35,
        backgroundColor: '#6366f1',
        justifyContent: 'center',
        alignItems: 'center',
        borderWidth: 4,
        borderColor: '#eef2ff',
    },
    avatarText: {
        fontSize: 28,
        fontWeight: '700',
        color: '#fff',
    },
    userInfo: {
        flex: 1,
    },
    userName: {
        fontSize: 22,
        fontWeight: '800',
        color: '#1e293b',
    },
    userRole: {
        fontSize: 14,
        color: '#64748b',
        fontWeight: '600',
        marginTop: 2,
        textTransform: 'capitalize',
    },
    mobileRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        marginTop: 6,
    },
    userMobile: {
        fontSize: 14,
        color: '#94a3b8',
    },
    menuContainer: {
        padding: 20,
    },
    section: {
        marginBottom: 24,
    },
    sectionTitle: {
        fontSize: 13,
        fontWeight: '700',
        color: '#94a3b8',
        marginBottom: 10,
        textTransform: 'uppercase',
        marginLeft: 4,
    },
    sectionContent: {
        backgroundColor: '#fff',
        borderRadius: 16,
        borderWidth: 1,
        borderColor: '#e2e8f0',
        overflow: 'hidden',
    },
    menuItem: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: 16,
    },
    menuItemBorder: {
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    menuItemLeft: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
    },
    iconBox: {
        width: 36,
        height: 36,
        borderRadius: 10,
        backgroundColor: '#eef2ff',
        justifyContent: 'center',
        alignItems: 'center',
    },
    menuItemLabel: {
        fontSize: 15,
        fontWeight: '600',
        color: '#334155',
    },
    logoutBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: '#fee2e2',
        padding: 16,
        borderRadius: 16,
        gap: 10,
        marginTop: 8,
    },
    logoutText: {
        fontSize: 16,
        fontWeight: '700',
        color: '#ef4444',
    },
    versionText: {
        textAlign: 'center',
        color: '#cbd5e1',
        fontSize: 12,
        marginTop: 24,
    },
});
