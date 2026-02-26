import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, ActivityIndicator, Dimensions, RefreshControl, Platform } from 'react-native';
import { BarChart, PieChart } from 'react-native-chart-kit';
import { apiCall } from '../services/api';
import { getUser } from '../services/auth';
import { useRouter } from 'expo-router';
import { TrendingUp, Users, Phone, PieChart as PieIcon, BarChart as BarIcon, AlertCircle, RefreshCcw, ShieldCheck, Clock } from 'lucide-react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

const screenWidth = Dimensions.get('window').width;

export default function ReportsScreen() {
    const router = useRouter();
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState<any>(null);
    const [error, setError] = useState<string | null>(null);
    const [refreshing, setRefreshing] = useState(false);
    const [userRole, setUserRole] = useState<string | null>(null);
    const [activeReport, setActiveReport] = useState<'summary' | 'business'>('summary');
    const [businessData, setBusinessData] = useState<any[]>([]);
    const [loadingBusiness, setLoadingBusiness] = useState(false);

    const fetchReports = async () => {
        if (!refreshing) setLoading(true);
        setError(null);

        const userData = await getUser();
        setUserRole(userData?.role || 'executive');

        if (userData?.role !== 'admin') {
            setLoading(false);
            setRefreshing(false);
            return;
        }

        const res = await apiCall('reports.php?action=summary');
        if (res.success) {
            setData(res.data);
        } else {
            setError(res.message || 'Failed to load reports');
        }

        if (activeReport === 'business') {
            await fetchBusinessReport();
        }

        setLoading(false);
        setRefreshing(false);
    };

    const fetchBusinessReport = async () => {
        setLoadingBusiness(true);
        const res = await apiCall('reports.php?action=business_calls_report');
        if (res.success) {
            setBusinessData(res.data);
        }
        setLoadingBusiness(false);
    };

    useEffect(() => {
        fetchReports();
    }, []);

    useEffect(() => {
        if (activeReport === 'business' && businessData.length === 0) {
            fetchBusinessReport();
        }
    }, [activeReport]);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchReports();
    }, []);

    if (loading && !refreshing) {
        return (
            <SafeAreaView style={styles.safeContainer}>
                <View style={styles.center}>
                    <ActivityIndicator size="large" color="#6366f1" />
                </View>
            </SafeAreaView>
        );
    }

    if (userRole && userRole !== 'admin') {
        return (
            <SafeAreaView style={styles.safeContainer}>
                <View style={styles.center}>
                    <ShieldCheck size={64} color="#ef4444" style={{ marginBottom: 20 }} />
                    <Text style={styles.errorText}>Access Denied</Text>
                    <Text style={styles.errorSubText}>This section is restricted to administrators only.</Text>
                    <TouchableOpacity style={styles.retryBtn} onPress={() => router.back()}>
                        <Text style={styles.retryText}>Go Back</Text>
                    </TouchableOpacity>
                </View>
            </SafeAreaView>
        );
    }

    if (error) {
        return (
            <SafeAreaView style={styles.safeContainer}>
                <View style={styles.center}>
                    <AlertCircle size={48} color="#ef4444" style={{ marginBottom: 16 }} />
                    <Text style={styles.errorText}>Error Loading Reports</Text>
                    <Text style={styles.errorSubText}>{error}</Text>
                    <TouchableOpacity style={styles.retryBtn} onPress={fetchReports}>
                        <RefreshCcw size={18} color="#fff" style={{ marginRight: 8 }} />
                        <Text style={styles.retryText}>Try Again</Text>
                    </TouchableOpacity>
                </View>
            </SafeAreaView>
        );
    }

    const chartConfig = {
        backgroundGradientFrom: '#fff',
        backgroundGradientTo: '#fff',
        color: (opacity = 1) => `rgba(99, 102, 241, ${opacity})`,
        labelColor: (opacity = 1) => `rgba(100, 116, 139, ${opacity})`,
        strokeWidth: 2,
        barPercentage: 0.6,
        useShadowColorFromDataset: false
    };

    const statusColors: any = {
        'New': '#94a3b8',
        'Follow-up': '#f59e0b',
        'Interested': '#6366f1',
        'Converted': '#10b981',
        'Lost': '#ef4444'
    };

    const pieData = (data?.status_distribution || [])
        .filter((item: any) => item && item.status)
        .map((item: any) => ({
            name: item.status,
            population: parseInt(item.count) || 0,
            color: statusColors[item.status] || '#cbd5e1',
            legendFontColor: '#64748b',
            legendFontSize: 12
        }));

    const validTeamData = (data?.team_performance || []).filter((item: any) => item && item.name);

    const barData = {
        labels: validTeamData.map((item: any) => (item.name || 'User').split(' ')[0]),
        datasets: [{
            data: validTeamData.map((item: any) => parseInt(item.total_calls) || 0)
        }]
    };

    if (!data) {
        return (
            <SafeAreaView style={styles.safeContainer}>
                <View style={styles.center}>
                    <ActivityIndicator size="large" color="#6366f1" />
                </View>
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={styles.safeContainer}>
            <View style={styles.headerRow}>
                <Text style={styles.mainTitle}>Performance Hub</Text>
                <TrendingUp size={24} color="#6366f1" />
            </View>

            <View style={styles.tabContainer}>
                <TouchableOpacity
                    style={[styles.tab, activeReport === 'summary' && styles.activeTab]}
                    onPress={() => setActiveReport('summary')}
                >
                    <PieIcon size={16} color={activeReport === 'summary' ? '#fff' : '#64748b'} />
                    <Text style={[styles.tabText, activeReport === 'summary' && styles.activeTabText]}>Overview</Text>
                </TouchableOpacity>
                <TouchableOpacity
                    style={[styles.tab, activeReport === 'business' && styles.activeTab]}
                    onPress={() => setActiveReport('business')}
                >
                    <Phone size={16} color={activeReport === 'business' ? '#fff' : '#64748b'} />
                    <Text style={[styles.tabText, activeReport === 'business' && styles.activeTabText]}>Business Calls</Text>
                </TouchableOpacity>
            </View>

            <ScrollView
                style={styles.container}
                contentContainerStyle={{ padding: 16 }}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
            >
                {activeReport === 'summary' ? (
                    <>
                        {/* Pipeline Status */}
                        <View style={styles.card}>
                            <View style={styles.cardHeader}>
                                <PieIcon size={20} color="#6366f1" />
                                <Text style={styles.cardTitle}>Lead Pipeline Status</Text>
                            </View>
                            {pieData && pieData.length > 0 ? (
                                <PieChart
                                    data={pieData}
                                    width={screenWidth - 64}
                                    height={200}
                                    chartConfig={chartConfig}
                                    accessor={"population"}
                                    backgroundColor={"transparent"}
                                    paddingLeft={"15"}
                                    center={[10, 0]}
                                    absolute
                                />
                            ) : <Text style={styles.empty}>No lead data available</Text>}
                        </View>

                        {/* Team Performance */}
                        <View style={styles.card}>
                            <View style={styles.cardHeader}>
                                <BarIcon size={20} color="#6366f1" />
                                <Text style={styles.cardTitle}>Calls by Executive</Text>
                            </View>
                            {barData.labels.length > 0 ? (
                                <BarChart
                                    style={{ marginVertical: 8, borderRadius: 16 }}
                                    data={barData}
                                    width={screenWidth - 64}
                                    height={220}
                                    yAxisLabel=""
                                    yAxisSuffix=""
                                    chartConfig={chartConfig}
                                    verticalLabelRotation={30}
                                />
                            ) : <Text style={styles.empty}>No team activity recorded</Text>}
                        </View>

                        {/* Performance Stats */}
                        <Text style={styles.sectionTitle}>Performance Overview</Text>
                        {validTeamData.map((item: any) => (
                            <View key={item.id || item.name} style={styles.performanceRow}>
                                <View style={styles.execInfo}>
                                    <Users size={18} color="#94a3b8" />
                                    <Text style={styles.execName}>{item.name}</Text>
                                </View>
                                <View style={styles.stats}>
                                    <View style={styles.pill}>
                                        <Text style={styles.pillText}>{item.total_calls || 0} Calls</Text>
                                    </View>
                                    <View style={[styles.pill, { backgroundColor: '#eef2ff' }]}>
                                        <Text style={[styles.pillText, { color: '#6366f1' }]}>{item.total_leads || 0} Leads</Text>
                                    </View>
                                </View>
                            </View>
                        ))}
                        {validTeamData.length === 0 && <Text style={styles.empty}>No team data</Text>}
                    </>
                ) : (
                    <View style={styles.businessReportContainer}>
                        <View style={styles.reportHeader}>
                            <View>
                                <Text style={styles.sectionTitle}>Business Calls</Text>
                                <Text style={styles.reportSubtitle}>Authenticated lead calls only</Text>
                            </View>
                            <TrendingUp size={24} color="#6366f1" />
                        </View>

                        {loadingBusiness ? (
                            <ActivityIndicator size="small" color="#6366f1" style={{ marginTop: 20 }} />
                        ) : businessData.length > 0 ? (
                            businessData.map((item, index) => (
                                <View key={index} style={styles.businessRow}>
                                    <View style={styles.businessHeader}>
                                        <View>
                                            <Text style={styles.execName}>{item.executive_name}</Text>
                                            <Text style={styles.dateText}>{item.call_date}</Text>
                                        </View>
                                        <View style={styles.durationBadge}>
                                            <Clock size={12} color="#10b981" style={{ marginRight: 4 }} />
                                            <Text style={styles.durationText}>
                                                {Math.floor(parseInt(item.total_duration) / 60)}m {parseInt(item.total_duration) % 60}s
                                            </Text>
                                        </View>
                                    </View>
                                    <View style={styles.businessFooter}>
                                        <View style={styles.countInfo}>
                                            <Phone size={14} color="#6366f1" />
                                            <Text style={styles.countText}>{item.call_count} Authenticated Calls</Text>
                                        </View>
                                        <View style={styles.typeBadge}>
                                            <Text style={styles.typeText}>LEAD MATCH</Text>
                                        </View>
                                    </View>
                                </View>
                            ))
                        ) : (
                            <Text style={styles.empty}>No business call data found</Text>
                        )}
                    </View>
                )}
            </ScrollView>
        </SafeAreaView>
    );
}

const styles = StyleSheet.create({
    safeContainer: {
        flex: 1,
        backgroundColor: '#f8fafc',
    },
    container: {
        flex: 1,
    },
    headerRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: 20,
        paddingVertical: 15,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    mainTitle: {
        fontSize: 22,
        fontWeight: '900',
        color: '#0f172a',
    },
    center: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
        padding: 24,
    },
    tabContainer: {
        flexDirection: 'row',
        backgroundColor: '#fff',
        padding: 8,
        marginHorizontal: 16,
        marginTop: 16,
        borderRadius: 12,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        gap: 8,
    },
    tab: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        paddingVertical: 10,
        borderRadius: 8,
        gap: 8,
    },
    activeTab: {
        backgroundColor: '#6366f1',
    },
    tabText: {
        fontSize: 13,
        fontWeight: '700',
        color: '#64748b',
    },
    activeTabText: {
        color: '#fff',
    },
    card: {
        backgroundColor: '#fff',
        borderRadius: 20,
        padding: 16,
        marginBottom: 16,
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 10,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    cardHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 10,
        marginBottom: 16,
    },
    cardTitle: {
        fontSize: 16,
        fontWeight: '800',
        color: '#1e293b',
    },
    sectionTitle: {
        fontSize: 18,
        fontWeight: '800',
        color: '#1e293b',
        marginVertical: 16,
    },
    businessReportContainer: {
        paddingTop: 8,
    },
    reportHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 16,
    },
    reportSubtitle: {
        fontSize: 13,
        color: '#64748b',
        marginTop: -12,
        marginBottom: 16,
    },
    businessRow: {
        backgroundColor: '#fff',
        padding: 16,
        borderRadius: 16,
        marginBottom: 12,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        elevation: 1,
    },
    businessHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: 12,
    },
    dateText: {
        fontSize: 12,
        color: '#94a3b8',
        marginTop: 2,
    },
    durationBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f0fdf4',
        paddingHorizontal: 10,
        paddingVertical: 4,
        borderRadius: 8,
    },
    durationText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#10b981',
    },
    businessFooter: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingTop: 12,
        borderTopWidth: 1,
        borderTopColor: '#f8fafc',
    },
    countInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
    },
    countText: {
        fontSize: 13,
        fontWeight: '600',
        color: '#475569',
    },
    typeBadge: {
        backgroundColor: '#eef2ff',
        paddingHorizontal: 8,
        paddingVertical: 4,
        borderRadius: 4,
    },
    typeText: {
        fontSize: 10,
        fontWeight: '800',
        color: '#6366f1',
    },
    performanceRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        backgroundColor: '#fff',
        padding: 16,
        borderRadius: 16,
        marginBottom: 8,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    execInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
    },
    execName: {
        fontSize: 15,
        fontWeight: '700',
        color: '#334155',
    },
    stats: {
        flexDirection: 'row',
        gap: 8,
    },
    pill: {
        backgroundColor: '#f1f5f9',
        paddingHorizontal: 10,
        paddingVertical: 4,
        borderRadius: 8,
    },
    pillText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
    },
    errorText: {
        fontSize: 18,
        fontWeight: '800',
        color: '#1e293b',
        marginBottom: 8,
    },
    errorSubText: {
        fontSize: 14,
        color: '#64748b',
        textAlign: 'center',
        marginBottom: 24,
        lineHeight: 20,
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
    empty: {
        textAlign: 'center',
        color: '#94a3b8',
        paddingVertical: 20,
    }
});
