import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, ScrollView, StyleSheet, RefreshControl, TouchableOpacity, Dimensions, ActivityIndicator } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { apiCall } from '../../services/api';
import { getUser } from '../../services/auth';
import { Users, CalendarClock, CheckCircle2, TrendingUp, PhoneCall, ArrowUpRight, Clock, Target, ChevronRight, BarChart3, ShieldCheck, Zap } from 'lucide-react-native';
import { useRouter } from 'expo-router';

const { width } = Dimensions.get('window');

export default function Dashboard() {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<any>(null);
    const [user, setUser] = useState<any>(null);
    const [refreshing, setRefreshing] = useState(false);
    const router = useRouter();

    const fetchData = async () => {
        if (!refreshing) setLoading(true);
        const userData = await getUser();
        setUser(userData);

        const res = await apiCall('dashboard.php');
        if (res.success) {
            setData(res.data);
        }
        setLoading(false);
    };

    useFocusEffect(
        useCallback(() => {
            fetchData();
        }, [])
    );

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchData();
        setRefreshing(false);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Converted': return '#10b981';
            case 'Interested': return '#6366f1';
            case 'Lost': return '#ef4444';
            case 'Follow-up': return '#f59e0b';
            default: return '#94a3b8';
        }
    };

    if (loading && !refreshing) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
            </View>
        );
    }

    // Role detection: prioritize API data, fallback to SecureStore user role
    const effectiveRole = data?.role || user?.role;
    const isAdmin = effectiveRole?.toLowerCase() === 'admin';
    const stats = data?.stats;

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <View>
                    <Text style={styles.greeting}>Welcome back,</Text>
                    <Text style={styles.userName}>{user?.name || 'User'}</Text>
                </View>
                <View style={[styles.roleBadge, { backgroundColor: isAdmin ? '#eef2ff' : '#f0fdf4' }]}>
                    {isAdmin ? <ShieldCheck size={14} color="#6366f1" /> : <Zap size={14} color="#10b981" />}
                    <Text style={[styles.roleText, { color: isAdmin ? '#6366f1' : '#10b981' }]}>
                        {isAdmin ? 'Admin' : 'Executive'}
                    </Text>
                </View>
            </View>

            <ScrollView
                showsVerticalScrollIndicator={false}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            >
                {isAdmin ? (
                    /* ADMIN VIEW */
                    <View style={styles.content}>
                        <View style={styles.kpiContainer}>
                            <TouchableOpacity style={[styles.kpiCard, { backgroundColor: '#6366f1' }]} onPress={() => router.push('/leads')}>
                                <Text style={styles.kpiNum}>{stats?.total_leads || 0}</Text>
                                <Text style={styles.kpiLabel}>Total Leads</Text>
                            </TouchableOpacity>
                            <View style={[styles.kpiCard, { backgroundColor: '#10b981' }]}>
                                <Text style={styles.kpiNum}>{stats?.today_calls || 0}</Text>
                                <Text style={styles.kpiLabel}>Calls Today</Text>
                            </View>
                            <View style={[styles.kpiCard, { backgroundColor: '#f59e0b' }]}>
                                <Text style={styles.kpiNum}>{stats?.today_leads || 0}</Text>
                                <Text style={styles.kpiLabel}>New Today</Text>
                            </View>
                        </View>

                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>Organization Overview</Text>
                            <View style={styles.statsGrid}>
                                <View style={styles.statBox}>
                                    <View style={[styles.iconCircle, { backgroundColor: '#fdf2f8' }]}>
                                        <TrendingUp size={18} color="#db2777" />
                                    </View>
                                    <View>
                                        <Text style={styles.statBoxVal}>{stats?.converted_leads || 0}</Text>
                                        <Text style={styles.statBoxLabel}>Converted</Text>
                                    </View>
                                </View>
                                <TouchableOpacity style={styles.statBox} onPress={() => router.push('/users')}>
                                    <View style={[styles.iconCircle, { backgroundColor: '#e0f2fe' }]}>
                                        <Users size={18} color="#0284c7" />
                                    </View>
                                    <View>
                                        <Text style={styles.statBoxVal}>{stats?.active_executives || 0}</Text>
                                        <Text style={styles.statBoxLabel}>Active Team</Text>
                                    </View>
                                </TouchableOpacity>
                            </View>
                        </View>

                        <TouchableOpacity style={styles.reportBanner} onPress={() => router.push('/reports')}>
                            <View style={styles.bannerInfo}>
                                <View style={styles.bannerIcon}>
                                    <BarChart3 color="#fff" size={20} />
                                </View>
                                <View>
                                    <Text style={styles.bannerTitle}>Performance Reports</Text>
                                    <Text style={styles.bannerSub}>Analyze team productivity & conversions</Text>
                                </View>
                            </View>
                            <ChevronRight color="#fff" size={20} />
                        </TouchableOpacity>

                        {/* Executive Performance Today */}
                        <View style={styles.section}>
                            <View style={styles.sectionHeader}>
                                <Text style={styles.sectionTitle}>Team Activity Today</Text>
                                <TouchableOpacity onPress={() => router.push('/users')}>
                                    <Text style={styles.seeAll}>View All</Text>
                                </TouchableOpacity>
                            </View>
                            {data?.executive_performance?.length > 0 ? (
                                data.executive_performance.map((exec: any) => (
                                    <View key={exec.id} style={styles.execStatCard}>
                                        <View style={styles.execStatHeader}>
                                            <View style={styles.execAvatarSmall}>
                                                <Text style={styles.execAvatarTextSmall}>{(exec.name || 'U').charAt(0).toUpperCase()}</Text>
                                            </View>
                                            <Text style={styles.execNameText}>{exec.name}</Text>
                                            <View style={[styles.taskBadge, { backgroundColor: exec.pending_tasks > 0 ? '#fee2e2' : '#f0fdf4' }]}>
                                                <Clock size={10} color={exec.pending_tasks > 0 ? '#ef4444' : '#10b981'} />
                                                <Text style={[styles.taskBadgeText, { color: exec.pending_tasks > 0 ? '#ef4444' : '#10b981' }]}>
                                                    {exec.pending_tasks} Tasks
                                                </Text>
                                            </View>
                                        </View>
                                        <View style={styles.execStatGrid}>
                                            <View style={styles.execGridItem}>
                                                <Text style={styles.gridVal}>{exec.total_calls}</Text>
                                                <Text style={styles.gridLabel}>Total</Text>
                                            </View>
                                            <View style={styles.execGridItem}>
                                                <Text style={[styles.gridVal, { color: '#ef4444' }]}>{exec.missed_calls}</Text>
                                                <Text style={styles.gridLabel}>Missed</Text>
                                            </View>
                                            <View style={styles.execGridItem}>
                                                <Text style={[styles.gridVal, { color: '#10b981' }]}>{exec.incoming_calls}</Text>
                                                <Text style={styles.gridLabel}>In</Text>
                                            </View>
                                            <View style={styles.execGridItem}>
                                                <Text style={[styles.gridVal, { color: '#6366f1' }]}>{exec.outgoing_calls}</Text>
                                                <Text style={styles.gridLabel}>Out</Text>
                                            </View>
                                        </View>
                                    </View>
                                ))
                            ) : (
                                <Text style={styles.empty}>No team activity recorded today.</Text>
                            )}
                        </View>
                    </View>
                ) : (
                    /* EXECUTIVE VIEW */
                    <View style={styles.content}>
                        <View style={styles.kpiContainer}>
                            <TouchableOpacity style={[styles.kpiCard, { backgroundColor: '#6366f1' }]} onPress={() => router.push('/tasks')}>
                                <Text style={styles.kpiNum}>{stats?.pending_tasks || 0}</Text>
                                <Text style={styles.kpiLabel}>Tasks Left</Text>
                            </TouchableOpacity>
                            <View style={[styles.kpiCard, { backgroundColor: '#10b981' }]}>
                                <Text style={styles.kpiNum}>{stats?.completed_tasks || 0}</Text>
                                <Text style={styles.kpiLabel}>Completed</Text>
                            </View>
                            <View style={[styles.kpiCard, { backgroundColor: '#f59e0b' }]}>
                                <Text style={styles.kpiNum}>{stats?.performance_percent || 0}%</Text>
                                <Text style={styles.kpiLabel}>Progress</Text>
                            </View>
                        </View>

                        <View style={styles.section}>
                            <View style={styles.goalCard}>
                                <View style={styles.goalHeader}>
                                    <View>
                                        <Text style={styles.goalTitle}>Daily Progress</Text>
                                        <Text style={styles.goalSub}>{stats?.completed_tasks} of {stats?.today_tasks} tasks done</Text>
                                    </View>
                                    <Target size={24} color="#6366f1" />
                                </View>
                                <View style={styles.progressContainer}>
                                    <View style={styles.progressBar}>
                                        <View style={[styles.progressFill, { width: `${stats?.performance_percent || 0}%` }]} />
                                    </View>
                                </View>
                            </View>
                        </View>

                        <View style={styles.section}>
                            <Text style={styles.sectionTitle}>My Status</Text>
                            <View style={styles.statsGrid}>
                                <TouchableOpacity style={styles.statBox} onPress={() => router.push('/leads')}>
                                    <View style={[styles.iconCircle, { backgroundColor: '#eef2ff' }]}>
                                        <Users size={18} color="#6366f1" />
                                    </View>
                                    <View>
                                        <Text style={styles.statBoxVal}>{stats?.my_leads || 0}</Text>
                                        <Text style={styles.statBoxLabel}>My Leads</Text>
                                    </View>
                                </TouchableOpacity>
                                <View style={styles.statBox}>
                                    <View style={[styles.iconCircle, { backgroundColor: '#f0fdf4' }]}>
                                        <CheckCircle2 size={18} color="#10b981" />
                                    </View>
                                    <View>
                                        <Text style={styles.statBoxVal}>{stats?.my_converted || 0}</Text>
                                        <Text style={styles.statBoxLabel}>Converted</Text>
                                    </View>
                                </View>
                            </View>
                        </View>
                    </View>
                )}

                {/* Shared Section: Recent Activity */}
                <View style={[styles.section, { marginBottom: 30 }]}>
                    <View style={styles.sectionHeader}>
                        <Text style={styles.sectionTitle}>{isAdmin ? 'All Leads' : 'My Leads'}</Text>
                        <TouchableOpacity onPress={() => router.push('/leads')}>
                            <Text style={styles.seeAll}>View All</Text>
                        </TouchableOpacity>
                    </View>

                    {data?.recent_leads?.length > 0 ? (
                        data.recent_leads.map((item: any) => (
                            <TouchableOpacity key={item.id} style={styles.prospectItem} onPress={() => router.push('/leads')}>
                                <View style={[styles.statusStrip, { backgroundColor: getStatusColor(item.status) }]} />
                                <View style={styles.prospectInfo}>
                                    <Text style={styles.prospectName}>{item.name}</Text>
                                    <Text style={styles.prospectMeta}>
                                        {item.mobile} {isAdmin && item.assigned_to_name ? `• ${item.assigned_to_name}` : ''}
                                    </Text>
                                </View>
                                <View style={[styles.statusBadge, { borderColor: getStatusColor(item.status) }]}>
                                    <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>{item.status}</Text>
                                </View>
                            </TouchableOpacity>
                        ))
                    ) : (
                        <Text style={styles.empty}>No recent leads found</Text>
                    )}
                </View>
            </ScrollView>
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
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: 20,
        paddingTop: 24,
        paddingBottom: 20,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    greeting: {
        fontSize: 13,
        color: '#64748b',
        fontWeight: '600',
    },
    userName: {
        fontSize: 22,
        fontWeight: '800',
        color: '#0f172a',
        marginTop: 2,
    },
    roleBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 10,
        paddingVertical: 5,
        borderRadius: 10,
        gap: 6,
    },
    roleText: {
        fontSize: 12,
        fontWeight: '700',
    },
    content: {
        paddingTop: 16,
    },
    kpiContainer: {
        flexDirection: 'row',
        paddingHorizontal: 20,
        gap: 12,
        marginBottom: 24,
    },
    kpiCard: {
        flex: 1,
        padding: 16,
        borderRadius: 20,
        alignItems: 'center',
        elevation: 4,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.1,
        shadowRadius: 8,
    },
    kpiNum: {
        fontSize: 22,
        fontWeight: '800',
        color: '#fff',
    },
    kpiLabel: {
        fontSize: 10,
        color: 'rgba(255,255,255,0.8)',
        fontWeight: '700',
        textTransform: 'uppercase',
        marginTop: 4,
    },
    section: {
        paddingHorizontal: 20,
        marginBottom: 24,
    },
    sectionHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 16,
    },
    sectionTitle: {
        fontSize: 17,
        fontWeight: '800',
        color: '#1e293b',
    },
    seeAll: {
        fontSize: 13,
        fontWeight: '700',
        color: '#6366f1',
    },
    statsGrid: {
        flexDirection: 'row',
        gap: 12,
    },
    statBox: {
        flex: 1,
        backgroundColor: '#fff',
        padding: 16,
        borderRadius: 20,
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    iconCircle: {
        width: 40,
        height: 40,
        borderRadius: 20,
        justifyContent: 'center',
        alignItems: 'center',
    },
    statBoxVal: {
        fontSize: 18,
        fontWeight: '800',
        color: '#0f172a',
    },
    statBoxLabel: {
        fontSize: 11,
        color: '#64748b',
        fontWeight: '600',
    },
    goalCard: {
        backgroundColor: '#fff',
        padding: 20,
        borderRadius: 24,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        elevation: 1,
    },
    goalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: 20,
    },
    goalTitle: {
        fontSize: 17,
        fontWeight: '800',
        color: '#0f172a',
    },
    goalSub: {
        fontSize: 13,
        color: '#64748b',
        marginTop: 4,
    },
    progressContainer: {
        height: 10,
        width: '100%',
    },
    progressBar: {
        height: 10,
        backgroundColor: '#f1f5f9',
        borderRadius: 5,
        overflow: 'hidden',
    },
    progressFill: {
        height: '100%',
        backgroundColor: '#6366f1',
        borderRadius: 5,
    },
    reportBanner: {
        marginHorizontal: 20,
        backgroundColor: '#6366f1',
        padding: 18,
        borderRadius: 20,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        marginBottom: 24,
    },
    bannerInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 16,
    },
    bannerIcon: {
        width: 42,
        height: 42,
        borderRadius: 14,
        backgroundColor: 'rgba(255,255,255,0.2)',
        justifyContent: 'center',
        alignItems: 'center',
    },
    bannerTitle: {
        color: '#fff',
        fontSize: 16,
        fontWeight: '800',
    },
    bannerSub: {
        color: 'rgba(255,255,255,0.8)',
        fontSize: 12,
        fontWeight: '600',
        marginTop: 2,
    },
    prospectItem: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#fff',
        padding: 14,
        borderRadius: 16,
        marginBottom: 10,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        elevation: 1,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 4,
    },
    statusStrip: {
        width: 4,
        height: 28,
        borderRadius: 2,
        marginRight: 14,
    },
    prospectInfo: {
        flex: 1,
    },
    prospectName: {
        fontSize: 15,
        fontWeight: '700',
        color: '#1e293b',
    },
    prospectMeta: {
        fontSize: 12,
        color: '#94a3b8',
        marginTop: 2,
    },
    statusBadge: {
        paddingHorizontal: 10,
        paddingVertical: 5,
        borderRadius: 8,
        borderWidth: 1,
    },
    statusText: {
        fontSize: 10,
        fontWeight: '800',
        textTransform: 'uppercase',
    },
    empty: {
        textAlign: 'center',
        color: '#94a3b8',
        marginTop: 20,
        fontSize: 14,
    },
    execStatCard: {
        backgroundColor: '#fff',
        borderRadius: 20,
        padding: 16,
        marginBottom: 12,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    execStatHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 16,
        gap: 12,
    },
    execAvatarSmall: {
        width: 32,
        height: 32,
        borderRadius: 16,
        backgroundColor: '#f5f3ff',
        justifyContent: 'center',
        alignItems: 'center',
    },
    execAvatarTextSmall: {
        color: '#6366f1',
        fontWeight: '700',
        fontSize: 12,
    },
    execNameText: {
        flex: 1,
        fontSize: 15,
        fontWeight: '700',
        color: '#1e293b',
    },
    taskBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 8,
        gap: 4,
    },
    taskBadgeText: {
        fontSize: 11,
        fontWeight: '700',
    },
    execStatGrid: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        backgroundColor: '#f8fafc',
        padding: 12,
        borderRadius: 16,
    },
    execGridItem: {
        alignItems: 'center',
        flex: 1,
    },
    gridVal: {
        fontSize: 16,
        fontWeight: '800',
        color: '#0f172a',
    },
    gridLabel: {
        fontSize: 10,
        color: '#64748b',
        fontWeight: '600',
        marginTop: 2,
    },
});
