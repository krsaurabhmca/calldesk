import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, Alert, RefreshControl } from 'react-native';
import { UserCog, MessageSquare, ChevronRight, User, Phone, LogOut, Info, Settings, BarChart3, Flag } from 'lucide-react-native';
import { useRouter } from 'expo-router';
import { getUser, logout } from '../../services/auth';

export default function MoreScreen() {
    const [user, setUser] = useState<any>(null);
    const [refreshing, setRefreshing] = useState(false);
    const router = useRouter();

    const fetchUser = async () => {
        const u = await getUser();
        setUser(u);
    };

    useEffect(() => {
        fetchUser();
    }, []);

    const onRefresh = React.useCallback(async () => {
        setRefreshing(true);
        await fetchUser();
        setRefreshing(false);
    }, []);

    const handleLogout = () => {
        Alert.alert('Logout', 'Are you sure you want to exit?', [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Logout',
                style: 'destructive',
                onPress: async () => {
                    await logout();
                    router.replace('/(auth)/login');
                }
            },
        ]);
    };

    const MenuButton = ({ icon: Icon, title, subtitle, onPress, color = '#6366f1', adminOnly = false }: any) => {
        if (adminOnly && user?.role !== 'admin') return null;

        return (
            <TouchableOpacity style={styles.menuItem} onPress={onPress}>
                <View style={[styles.iconBox, { backgroundColor: color + '15' }]}>
                    <Icon size={22} color={color} />
                </View>
                <View style={styles.menuInfo}>
                    <Text style={styles.menuTitle}>{title}</Text>
                    <Text style={styles.menuSubtitle}>{subtitle}</Text>
                </View>
                <ChevronRight size={18} color="#cbd5e1" />
            </TouchableOpacity>
        );
    };

    return (
        <ScrollView
            style={styles.container}
            contentContainerStyle={{ paddingBottom: 40 }}
            refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        >
            {/* Profile Header */}
            <View style={styles.header}>
                <View style={styles.profileBox}>
                    <View style={styles.avatar}>
                        <Text style={styles.avatarText}>{user?.name?.charAt(0) || 'U'}</Text>
                    </View>
                    <View>
                        <Text style={styles.userName}>{user?.name || 'Loading...'}</Text>
                        <Text style={styles.userRole}>{user?.role?.toUpperCase() || '...'}</Text>
                    </View>
                </View>
            </View>

            <View style={styles.section}>
                <Text style={styles.sectionHeader}>Operational Tools</Text>
                <MenuButton
                    icon={MessageSquare}
                    title="WhatsApp Templates"
                    subtitle="Manage quick reply messages"
                    onPress={() => router.push('/messages')}
                    color="#25D366"
                />
            </View>

            <View style={styles.section}>
                <Text style={styles.sectionHeader}>Administration</Text>
                <MenuButton
                    icon={UserCog}
                    title="Team Management"
                    subtitle="Manage executives and track activity"
                    onPress={() => router.push('/users')}
                    color="#6366f1"
                    adminOnly={true}
                />
                <MenuButton
                    icon={BarChart3}
                    title="Performance Reports"
                    subtitle="Visual analytics and team stats"
                    onPress={() => router.push('/reports')}
                    color="#f59e0b"
                    adminOnly={true}
                />
                <MenuButton
                    icon={Flag}
                    title="Lead Sources"
                    subtitle="Manage where your leads come from"
                    onPress={() => router.push('/sources')}
                    color="#8b5cf6"
                    adminOnly={true}
                />
                <MenuButton
                    icon={Settings}
                    title="System Settings"
                    subtitle="App configurations and preferences"
                    onPress={() => Alert.alert('Coming Soon', 'Settings will be available in the next update.')}
                    color="#64748b"
                    adminOnly={true}
                />
            </View>

            <View style={styles.section}>
                <Text style={styles.sectionHeader}>Account</Text>
                <MenuButton
                    icon={Info}
                    title="Help & Guide"
                    subtitle="How Calldesk helps your business"
                    onPress={() => router.push('/help')}
                    color="#6366f1"
                />
                <MenuButton
                    icon={Settings}
                    title="About Calldesk"
                    subtitle="Version 1.0.1 (Beta)"
                    onPress={() => { }}
                    color="#94a3b8"
                />
                <TouchableOpacity style={styles.logoutBtn} onPress={handleLogout}>
                    <LogOut size={20} color="#ef4444" />
                    <Text style={styles.logoutText}>Logout Session</Text>
                </TouchableOpacity>
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
        backgroundColor: '#fff',
        padding: 24,
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    profileBox: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 16,
    },
    avatar: {
        width: 60,
        height: 60,
        borderRadius: 20,
        backgroundColor: '#6366f1',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 4,
        shadowColor: '#6366f1',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.2,
        shadowRadius: 8,
    },
    avatarText: {
        color: '#fff',
        fontSize: 24,
        fontWeight: '800',
    },
    userName: {
        fontSize: 20,
        fontWeight: '800',
        color: '#0f172a',
    },
    userRole: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
        letterSpacing: 1,
        marginTop: 2,
    },
    section: {
        marginTop: 24,
        paddingHorizontal: 16,
    },
    sectionHeader: {
        fontSize: 13,
        fontWeight: '700',
        color: '#94a3b8',
        textTransform: 'uppercase',
        letterSpacing: 1,
        marginBottom: 12,
        marginLeft: 8,
    },
    menuItem: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#fff',
        padding: 16,
        borderRadius: 16,
        marginBottom: 8,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    iconBox: {
        width: 44,
        height: 44,
        borderRadius: 12,
        justifyContent: 'center',
        alignItems: 'center',
    },
    menuInfo: {
        flex: 1,
        marginLeft: 16,
    },
    menuTitle: {
        fontSize: 16,
        fontWeight: '700',
        color: '#1e293b',
    },
    menuSubtitle: {
        fontSize: 13,
        color: '#64748b',
        marginTop: 2,
    },
    logoutBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: 10,
        backgroundColor: '#fff',
        padding: 16,
        borderRadius: 16,
        marginTop: 8,
        borderWidth: 1,
        borderColor: '#fee2e2',
    },
    logoutText: {
        color: '#ef4444',
        fontSize: 16,
        fontWeight: '700',
    },
});
