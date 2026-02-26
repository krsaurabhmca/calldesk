import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator, RefreshControl, Modal, TextInput, Alert, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { UserPlus, Power, Activity, ChevronRight, Phone, User, Search, X, Clock, AlertCircle, RefreshCcw, ShieldCheck } from 'lucide-react-native';
import { useRouter } from 'expo-router';
import { apiCall } from '../../services/api';
import { useSnackbar } from '../../context/SnackbarContext';
import { getUser } from '../../services/auth';
import DateTimePicker from '@react-native-community/datetimepicker';

interface UserStats {
    total_users: number;
    active_users: number;
    calls_today: number;
    leads_today: number;
    follows_today: number;
}

interface UserData {
    id: number;
    name: string;
    mobile: string;
    role: 'admin' | 'executive';
    status: number;
    total_leads: number;
    calls_today: number;
    activities_today: number;
    created_at: string;
}

interface ActivityItem {
    id: number;
    activity_type: 'call' | 'followup';
    lead_name: string;
    mobile: string;
    type?: string; // for calls (Incoming, etc)
    duration?: number;
    remark?: string; // for followups
    time: string;
}

export default function UserManagement() {
    const { showSnackbar } = useSnackbar();
    const router = useRouter();
    const [loading, setLoading] = useState(true);
    const [stats, setStats] = useState<UserStats | null>(null);
    const [users, setUsers] = useState<UserData[]>([]);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [error, setError] = useState<string | null>(null);
    const [userRole, setUserRole] = useState<string | null>(null);

    // Modal states
    const [showAddModal, setShowAddModal] = useState(false);
    const [showActivityModal, setShowActivityModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState<UserData | null>(null);
    const [userActivity, setUserActivity] = useState<ActivityItem[]>([]);
    const [loadingActivity, setLoadingActivity] = useState(false);
    const [activityTypeFilter, setActivityTypeFilter] = useState('All');
    const [activityDate, setActivityDate] = useState<string>('');
    const [showActivityDatePicker, setShowActivityDatePicker] = useState(false);

    // Form states
    const [newName, setNewName] = useState('');
    const [newMobile, setNewMobile] = useState('');
    const [newPassword, setNewPassword] = useState('');
    const [newRole, setNewRole] = useState<'admin' | 'executive'>('executive');
    const [saving, setSaving] = useState(false);

    const fetchData = async () => {
        if (!refreshing) setLoading(true);
        setError(null);

        const userData = await getUser();
        setUserRole(userData?.role || 'executive');

        if (userData?.role !== 'admin') {
            setLoading(false);
            return;
        }

        const [statsRes, usersRes] = await Promise.all([
            apiCall('users_admin.php?action=stats'),
            apiCall('users_admin.php?action=list')
        ]);

        if (statsRes.success) {
            setStats(statsRes.data);
        } else {
            setError(statsRes.message);
        }

        if (usersRes.success) {
            setUsers(usersRes.data);
        } else {
            setError(usersRes.message);
        }

        setLoading(false);
        setRefreshing(false);
    };

    const onRefresh = useCallback(() => {
        setRefreshing(true);
        fetchData();
    }, []);

    useEffect(() => {
        fetchData();
    }, []);

    const handleAddUser = async () => {
        if (!newName || !newMobile || !newPassword) {
            showSnackbar('All fields are required', 'error');
            return;
        }

        setSaving(true);
        const res = await apiCall('users_admin.php', 'POST', {
            action: 'add',
            name: newName,
            mobile: newMobile,
            password: newPassword,
            role: newRole
        });

        if (res.success) {
            showSnackbar('User added successfully', 'success');
            setShowAddModal(false);
            setNewName('');
            setNewMobile('');
            setNewPassword('');
            fetchData();
        } else {
            showSnackbar(res.message || 'Failed to add user', 'error');
        }
        setSaving(false);
    };

    const toggleUserStatus = (user: UserData) => {
        const newStatus = user.status === 1 ? 0 : 1;
        Alert.alert(
            newStatus === 1 ? 'Activate User' : 'Deactivate User',
            `Are you sure you want to ${newStatus === 1 ? 'activate' : 'deactivate'} ${user.name}?`,
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Confirm',
                    onPress: async () => {
                        const res = await apiCall('users_admin.php', 'POST', {
                            action: 'toggle_status',
                            user_id: user.id,
                            status: newStatus
                        });
                        if (res.success) {
                            showSnackbar('Status updated', 'success');
                            fetchData();
                        } else {
                            showSnackbar(res.message, 'error');
                        }
                    }
                }
            ]
        );
    };

    const fetchUserActivity = async (user: UserData, date: string = '') => {
        setSelectedUser(user);
        if (!date) setActivityDate('');
        setShowActivityModal(true);
        setLoadingActivity(true);
        const res = await apiCall(`users_admin.php?action=activity&user_id=${user.id}&date=${date}`);
        if (res.success) {
            setUserActivity(res.data);
        }
        setLoadingActivity(false);
    };

    const handleActivityDateChange = (event: any, selectedDate?: Date) => {
        setShowActivityDatePicker(false);
        if (selectedDate && selectedUser) {
            const dateStr = selectedDate.toISOString().split('T')[0];
            setActivityDate(dateStr);
            fetchUserActivity(selectedUser, dateStr);
        }
    };

    const filteredUsers = Array.isArray(users) ? users.filter(u =>
        u.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
        u.mobile.includes(searchQuery)
    ) : [];

    const renderUser = ({ item }: { item: UserData }) => (
        <TouchableOpacity
            style={[styles.userCard, item.status === 0 && styles.inactiveCard]}
            onPress={() => fetchUserActivity(item)}
        >
            <View style={styles.userHeader}>
                <View style={styles.userInfo}>
                    <View style={[styles.avatar, { backgroundColor: item.role === 'admin' ? '#eef2ff' : '#f0fdf4' }]}>
                        <User color={item.role === 'admin' ? '#6366f1' : '#10b981'} size={20} />
                    </View>
                    <View>
                        <Text style={styles.userName}>{item.name}</Text>
                        <Text style={styles.userRole}>{item.role.toUpperCase()}</Text>
                    </View>
                </View>
                <TouchableOpacity onPress={() => toggleUserStatus(item)} style={styles.statusToggle}>
                    <Power color={item.status === 1 ? '#10b981' : '#ef4444'} size={18} />
                </TouchableOpacity>
            </View>

            <View style={styles.userStats}>
                <View style={styles.statItem}>
                    <Text style={styles.statVal}>{item.total_leads}</Text>
                    <Text style={styles.statLbl}>Leads</Text>
                </View>
                <View style={styles.statItem}>
                    <Text style={styles.statVal}>{item.calls_today}</Text>
                    <Text style={styles.statLbl}>Calls Today</Text>
                </View>
                <View style={styles.statItem}>
                    <Text style={styles.statVal}>{item.activities_today}</Text>
                    <Text style={styles.statLbl}>Activities</Text>
                </View>
            </View>

            <View style={styles.cardFooter}>
                <Text style={styles.userMobile}>{item.mobile}</Text>
                <ChevronRight color="#cbd5e1" size={16} />
            </View>
        </TouchableOpacity>
    );

    if (loading && !refreshing) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
            </View>
        );
    }

    if (userRole && userRole !== 'admin') {
        return (
            <View style={styles.center}>
                <ShieldCheck size={64} color="#ef4444" style={{ marginBottom: 20 }} />
                <Text style={styles.errorTitle}>Access Denied</Text>
                <Text style={styles.errorSub}>This section is restricted to administrators only.</Text>
                <TouchableOpacity style={styles.retryBtn} onPress={() => router.back()}>
                    <Text style={styles.retryText}>Go Back</Text>
                </TouchableOpacity>
            </View>
        );
    }

    if (error && !refreshing && users.length === 0) {
        return (
            <View style={styles.center}>
                <AlertCircle size={48} color="#ef4444" style={{ marginBottom: 16 }} />
                <Text style={styles.errorTitle}>Connection Error</Text>
                <Text style={styles.errorSub}>{error}</Text>
                <TouchableOpacity style={styles.retryBtn} onPress={fetchData}>
                    <RefreshCcw size={18} color="#fff" style={{ marginRight: 8 }} />
                    <Text style={styles.retryText}>Try Again</Text>
                </TouchableOpacity>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            {/* Header Stats */}
            <View style={styles.header}>
                <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.statsScroll}>
                    <View style={[styles.statBox, { backgroundColor: '#6366f1' }]}>
                        <Text style={styles.statBoxTitle}>Total Users</Text>
                        <Text style={styles.statBoxVal}>{stats?.total_users || 0}</Text>
                    </View>
                    <View style={[styles.statBox, { backgroundColor: '#10b981' }]}>
                        <Text style={styles.statBoxTitle}>Calls Today</Text>
                        <Text style={styles.statBoxVal}>{stats?.calls_today || 0}</Text>
                    </View>
                    <View style={[styles.statBox, { backgroundColor: '#f59e0b' }]}>
                        <Text style={styles.statBoxTitle}>New Leads</Text>
                        <Text style={styles.statBoxVal}>{stats?.leads_today || 0}</Text>
                    </View>
                    <View style={[styles.statBox, { backgroundColor: '#8b5cf6' }]}>
                        <Text style={styles.statBoxTitle}>Follow-ups</Text>
                        <Text style={styles.statBoxVal}>{stats?.follows_today || 0}</Text>
                    </View>
                </ScrollView>
            </View>

            {/* Search & Actions */}
            <View style={styles.searchBar}>
                <View style={styles.searchInputContainer}>
                    <Search size={18} color="#94a3b8" />
                    <TextInput
                        style={styles.input}
                        placeholder="Search team..."
                        value={searchQuery}
                        onChangeText={setSearchQuery}
                    />
                </View>
                <TouchableOpacity style={styles.addBtn} onPress={() => setShowAddModal(true)}>
                    <UserPlus color="#fff" size={20} />
                </TouchableOpacity>
            </View>

            <FlatList
                data={filteredUsers}
                renderItem={renderUser}
                keyExtractor={item => item.id.toString()}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                ListEmptyComponent={<Text style={styles.empty}>No team members found</Text>}
            />

            {/* Add User Modal */}
            <Modal visible={showAddModal} animationType="slide" transparent>
                <View style={styles.modalOverlay}>
                    <KeyboardAvoidingView
                        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                        style={{ width: '100%' }}
                    >
                        <View style={styles.modalContent}>
                            <View style={styles.modalHeader}>
                                <Text style={styles.modalTitle}>Team Management</Text>
                                <TouchableOpacity onPress={() => setShowAddModal(false)}>
                                    <X color="#64748b" size={24} />
                                </TouchableOpacity>
                            </View>

                            <TextInput
                                style={styles.formInput}
                                placeholder="Full Name"
                                value={newName}
                                onChangeText={setNewName}
                            />
                            <TextInput
                                style={styles.formInput}
                                placeholder="Mobile Number"
                                value={newMobile}
                                onChangeText={setNewMobile}
                                keyboardType="phone-pad"
                            />
                            <TextInput
                                style={styles.formInput}
                                placeholder="Password"
                                value={newPassword}
                                onChangeText={setNewPassword}
                                secureTextEntry
                            />

                            <View style={styles.roleContainer}>
                                <TouchableOpacity
                                    style={[styles.roleBtn, newRole === 'executive' && styles.roleBtnActive]}
                                    onPress={() => setNewRole('executive')}
                                >
                                    <Text style={[styles.roleText, newRole === 'executive' && styles.roleTextActive]}>Executive</Text>
                                </TouchableOpacity>
                                <TouchableOpacity
                                    style={[styles.roleBtn, newRole === 'admin' && styles.roleBtnActive]}
                                    onPress={() => setNewRole('admin')}
                                >
                                    <Text style={[styles.roleText, newRole === 'admin' && styles.roleTextActive]}>Admin</Text>
                                </TouchableOpacity>
                            </View>

                            <TouchableOpacity style={styles.saveBtn} onPress={handleAddUser} disabled={saving}>
                                {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveBtnText}>Save User</Text>}
                            </TouchableOpacity>
                        </View>
                    </KeyboardAvoidingView>
                </View>
            </Modal>

            {/* Activity Modal */}
            <Modal visible={showActivityModal} animationType="slide" transparent>
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContentLarge}>
                        <View style={styles.modalHeader}>
                            <View>
                                <Text style={styles.modalTitle}>{selectedUser?.name}'s Activity</Text>
                                <View style={styles.activityHeaderSub}>
                                    <TouchableOpacity
                                        style={styles.datePickerBtn}
                                        onPress={() => setShowActivityDatePicker(true)}
                                    >
                                        <Clock size={14} color="#6366f1" />
                                        <Text style={styles.datePickerText}>
                                            {activityDate || 'Last 50 Activities'}
                                        </Text>
                                    </TouchableOpacity>
                                    {activityDate !== '' && (
                                        <TouchableOpacity
                                            onPress={() => {
                                                setActivityDate('');
                                                if (selectedUser) fetchUserActivity(selectedUser, '');
                                            }}
                                            style={styles.clearDateBtn}
                                        >
                                            <X size={14} color="#ef4444" />
                                        </TouchableOpacity>
                                    )}
                                </View>
                            </View>
                            <TouchableOpacity onPress={() => setShowActivityModal(false)}>
                                <X color="#64748b" size={24} />
                            </TouchableOpacity>
                        </View>

                        {showActivityDatePicker && (
                            <DateTimePicker
                                value={activityDate ? new Date(activityDate) : new Date()}
                                mode="date"
                                display="default"
                                onChange={handleActivityDateChange}
                                maximumDate={new Date()}
                            />
                        )}

                        <View style={styles.activityFilterStrip}>
                            {['All', 'Calls', 'Follow-ups'].map((f) => (
                                <TouchableOpacity
                                    key={f}
                                    style={[styles.activityFilterBtn, activityTypeFilter === f && styles.activityFilterBtnActive]}
                                    onPress={() => setActivityTypeFilter(f)}
                                >
                                    <View style={styles.filterBtnContent}>
                                        {f === 'Calls' && <Phone size={12} color={activityTypeFilter === f ? '#fff' : '#64748b'} style={{ marginRight: 4 }} />}
                                        {f === 'Follow-ups' && <Activity size={12} color={activityTypeFilter === f ? '#fff' : '#64748b'} style={{ marginRight: 4 }} />}
                                        <Text style={[styles.activityFilterText, activityTypeFilter === f && styles.activityFilterTextActive]}>{f}</Text>
                                    </View>
                                </TouchableOpacity>
                            ))}
                        </View>

                        {loadingActivity ? (
                            <ActivityIndicator size="large" color="#6366f1" style={{ marginTop: 40 }} />
                        ) : (
                            <FlatList
                                data={userActivity.filter(a => {
                                    if (activityTypeFilter === 'All') return true;
                                    if (activityTypeFilter === 'Calls') return a.activity_type === 'call';
                                    if (activityTypeFilter === 'Follow-ups') return a.activity_type === 'followup';
                                    return true;
                                })}
                                keyExtractor={(item, index) => index.toString()}
                                contentContainerStyle={{ padding: 20 }}
                                renderItem={({ item }) => (
                                    <View style={styles.activityItem}>
                                        <View style={[styles.activityIcon, { backgroundColor: item.activity_type === 'call' ? '#e0f2fe' : '#fef3c7' }]}>
                                            {item.activity_type === 'call' ? <Phone size={14} color="#0284c7" /> : <Activity size={14} color="#d97706" />}
                                        </View>
                                        <View style={styles.activityInfo}>
                                            <View style={styles.activityHeader}>
                                                <Text style={styles.activityLead}>{item.lead_name || item.mobile || 'Unknown'}</Text>
                                                <Text style={styles.activityTime}>{new Date(item.time).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}</Text>
                                            </View>
                                            <Text style={styles.activityDesc}>
                                                {item.activity_type === 'call'
                                                    ? `${item.type} Call - ${item.duration}s`
                                                    : `Follow-up: ${item.remark}`}
                                            </Text>
                                            <Text style={styles.activityDate}>{new Date(item.time).toLocaleDateString()}</Text>
                                        </View>
                                    </View>
                                )}
                                ListEmptyComponent={<Text style={styles.empty}>No recent activity</Text>}
                            />
                        )}
                    </View>
                </View>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#f8fafc',
    },
    center: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        padding: 24,
    },
    header: {
        paddingVertical: 20,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
    },
    statsScroll: {
        paddingHorizontal: 20,
        gap: 12,
    },
    statBox: {
        width: 120,
        padding: 16,
        borderRadius: 16,
        justifyContent: 'center',
    },
    statBoxTitle: {
        color: 'rgba(255,255,255,0.8)',
        fontSize: 12,
        fontWeight: '600',
    },
    statBoxVal: {
        color: '#fff',
        fontSize: 22,
        fontWeight: '800',
        marginTop: 4,
    },
    searchBar: {
        flexDirection: 'row',
        padding: 20,
        gap: 12,
        alignItems: 'center',
    },
    searchInputContainer: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#fff',
        paddingHorizontal: 12,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: '#e2e8f0',
        height: 48,
    },
    input: {
        flex: 1,
        marginLeft: 8,
        fontSize: 15,
        color: '#1e293b',
    },
    addBtn: {
        width: 48,
        height: 48,
        backgroundColor: '#6366f1',
        borderRadius: 12,
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 2,
    },
    list: {
        padding: 20,
        paddingTop: 0,
    },
    userCard: {
        backgroundColor: '#fff',
        borderRadius: 20,
        padding: 16,
        marginBottom: 16,
        borderWidth: 1,
        borderColor: '#e2e8f0',
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 10,
        elevation: 2,
    },
    inactiveCard: {
        opacity: 0.6,
        backgroundColor: '#f1f5f9',
    },
    userHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 16,
    },
    userInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
    },
    avatar: {
        width: 40,
        height: 40,
        borderRadius: 20,
        justifyContent: 'center',
        alignItems: 'center',
    },
    userName: {
        fontSize: 16,
        fontWeight: '700',
        color: '#1e293b',
    },
    userRole: {
        fontSize: 10,
        fontWeight: '800',
        color: '#64748b',
        letterSpacing: 0.5,
    },
    statusToggle: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: '#fff',
        justifyContent: 'center',
        alignItems: 'center',
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    userStats: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        backgroundColor: '#f8fafc',
        padding: 12,
        borderRadius: 12,
        marginBottom: 12,
    },
    statItem: {
        alignItems: 'center',
        flex: 1,
    },
    statVal: {
        fontSize: 16,
        fontWeight: '700',
        color: '#1e293b',
    },
    statLbl: {
        fontSize: 10,
        color: '#64748b',
        fontWeight: '600',
    },
    cardFooter: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    userMobile: {
        fontSize: 13,
        color: '#64748b',
        fontWeight: '500',
    },
    empty: {
        textAlign: 'center',
        color: '#94a3b8',
        marginTop: 40,
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: '#fff',
        borderTopLeftRadius: 30,
        borderTopRightRadius: 30,
        padding: 24,
    },
    modalContentLarge: {
        backgroundColor: '#fff',
        borderTopLeftRadius: 30,
        borderTopRightRadius: 30,
        height: '80%',
    },
    modalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: 24,
        paddingHorizontal: 20,
        paddingTop: 20,
    },
    modalTitle: {
        fontSize: 20,
        fontWeight: '800',
        color: '#1e293b',
    },
    modalSubtitle: {
        fontSize: 13,
        color: '#64748b',
    },
    activityHeaderSub: {
        flexDirection: 'row',
        alignItems: 'center',
        marginTop: 4,
        gap: 8,
    },
    datePickerBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f1f5f9',
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 6,
        gap: 6,
    },
    datePickerText: {
        fontSize: 12,
        fontWeight: '600',
        color: '#6366f1',
    },
    clearDateBtn: {
        backgroundColor: '#fee2e2',
        padding: 4,
        borderRadius: 4,
    },
    formInput: {
        backgroundColor: '#f8fafc',
        height: 54,
        borderRadius: 16,
        paddingHorizontal: 16,
        fontSize: 15,
        marginBottom: 16,
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    roleContainer: {
        flexDirection: 'row',
        gap: 12,
        marginBottom: 24,
    },
    roleBtn: {
        flex: 1,
        height: 48,
        borderRadius: 16,
        backgroundColor: '#f1f5f9',
        justifyContent: 'center',
        alignItems: 'center',
    },
    roleBtnActive: {
        backgroundColor: '#6366f1',
    },
    roleText: {
        fontWeight: '700',
        color: '#64748b',
    },
    roleTextActive: {
        color: '#fff',
    },
    saveBtn: {
        backgroundColor: '#6366f1',
        height: 56,
        borderRadius: 16,
        justifyContent: 'center',
        alignItems: 'center',
    },
    saveBtnText: {
        color: '#fff',
        fontSize: 16,
        fontWeight: '700',
    },
    activityItem: {
        flexDirection: 'row',
        paddingVertical: 16,
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
        gap: 16,
    },
    activityIcon: {
        width: 32,
        height: 32,
        borderRadius: 10,
        justifyContent: 'center',
        alignItems: 'center',
    },
    activityInfo: {
        flex: 1,
    },
    activityHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 4,
    },
    activityLead: {
        fontSize: 15,
        fontWeight: '700',
        color: '#1e293b',
    },
    activityTime: {
        fontSize: 12,
        color: '#6366f1',
        fontWeight: '700',
    },
    activityDesc: {
        fontSize: 13,
        color: '#475569',
        marginBottom: 4,
    },
    activityDate: {
        fontSize: 11,
        color: '#94a3b8',
    },
    activityFilterStrip: {
        flexDirection: 'row',
        paddingHorizontal: 20,
        paddingBottom: 16,
        gap: 8,
    },
    activityFilterBtn: {
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 10,
        backgroundColor: '#f1f5f9',
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    activityFilterBtnActive: {
        backgroundColor: '#6366f1',
        borderColor: '#6366f1',
    },
    filterBtnContent: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    activityFilterText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
    },
    activityFilterTextActive: {
        color: '#fff',
    },
    errorTitle: {
        fontSize: 18,
        fontWeight: '800',
        color: '#1e293b',
        marginBottom: 8,
    },
    errorSub: {
        fontSize: 14,
        color: '#64748b',
        textAlign: 'center',
        marginBottom: 24,
    },
    retryBtn: {
        flexDirection: 'row',
        backgroundColor: '#6366f1',
        paddingHorizontal: 20,
        paddingVertical: 12,
        borderRadius: 12,
        alignItems: 'center',
    },
    retryText: {
        color: '#fff',
        fontSize: 16,
        fontWeight: '700',
    },
});
