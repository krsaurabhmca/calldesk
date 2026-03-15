import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator, RefreshControl, TouchableOpacity, Linking, Modal, TextInput, Alert, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { makeCall } from '../../services/dialer';
import { useFocusEffect } from '@react-navigation/native';
import { apiCall } from '../../services/api';
import { Calendar as CalendarIcon, MessageSquare, Clock, Phone, X, CheckCircle2, History, ChevronRight, MessageCircle } from 'lucide-react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useSnackbar } from '../../context/SnackbarContext';

const STATUS_OPTIONS = ['Pending', 'Follow-up', 'Interested', 'Converted', 'Lost'];

export default function TasksScreen() {
    const { showSnackbar } = useSnackbar();
    const [tasks, setTasks] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [filter, setFilter] = useState('today'); // 'today', 'upcoming'
    const [subFilter, setSubFilter] = useState('pending'); // 'pending', 'completed'
    const [filteredTasks, setFilteredTasks] = useState([]);
    const [whatsappMessages, setWhatsappMessages] = useState<any[]>([]);

    // Update Task Modal State
    const [updateModalVisible, setUpdateModalVisible] = useState(false);
    const [selectedTask, setSelectedTask] = useState<any>(null);
    const [updateRemark, setUpdateRemark] = useState('');
    const [updateStatus, setUpdateStatus] = useState('');
    const [nextFollowUp, setNextFollowUp] = useState('');
    const [activeTab, setActiveTab] = useState<'update' | 'history'>('update');
    const [submitting, setSubmitting] = useState(false);
    const [history, setHistory] = useState<any[]>([]);
    const [loadingHistory, setLoadingHistory] = useState(false);

    // Date Picker State
    const [showDatePicker, setShowDatePicker] = useState(false);

    const fetchTasks = async (currentFilter: string) => {
        const [taskRes, waRes] = await Promise.all([
            apiCall(`tasks.php?filter=${currentFilter}`),
            apiCall('whatsapp_messages.php')
        ]);

        if (taskRes.success) {
            setTasks(taskRes.data.tasks);
        }
        if (waRes.success) {
            setWhatsappMessages(waRes.data);
        }
        setLoading(false);
    };

    const fetchHistory = async (leadId: number) => {
        setLoadingHistory(true);
        const result = await apiCall(`history.php?lead_id=${leadId}`);
        if (result.success) {
            setHistory(result.data);
        }
        setLoadingHistory(false);
    };

    useFocusEffect(
        useCallback(() => {
            fetchTasks(filter);
        }, [filter])
    );

    useEffect(() => {
        if (!Array.isArray(tasks)) {
            setFilteredTasks([]);
            return;
        }
        if (filter === 'today') {
            const isCompleted = subFilter === 'completed' ? 1 : 0;
            setFilteredTasks(tasks.filter((t: any) => parseInt(t.is_completed) === isCompleted));
        } else {
            setFilteredTasks(tasks);
        }
    }, [subFilter, filter, tasks]);

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchTasks(filter);
        setRefreshing(false);
    };

    const handleCall = (mobile: string) => {
        makeCall(mobile);
    };

    const handleWhatsApp = (mobile: string) => {
        const defaultMsg = whatsappMessages.find(m => m.is_default == 1);
        const text = defaultMsg ? defaultMsg.message : 'Hello, this is regarding your inquiry with Calldesk.';
        Linking.openURL(`whatsapp://send?phone=91${mobile}&text=${encodeURIComponent(text)}`);
    };

    const getFormattedDate = (daysToAdd: number) => {
        const date = new Date();
        date.setDate(date.getDate() + daysToAdd);
        return date.toISOString().split('T')[0];
    };

    const onDateChange = (event: any, selectedDate?: Date) => {
        setShowDatePicker(false);
        if (selectedDate) {
            setNextFollowUp(selectedDate.toISOString().split('T')[0]);
        }
    };

    const openUpdateModal = (task: any) => {
        setSelectedTask(task);
        setUpdateStatus(task.lead_status);
        setUpdateRemark('');
        setHistory([]);
        setNextFollowUp('');
        setActiveTab('update');
        setUpdateModalVisible(true);
        fetchHistory(task.lead_id);
    };

    const handleUpdateTask = async () => {
        if (!updateRemark) {
            showSnackbar('Please enter a remark', 'error');
            return;
        }

        const finalDate = nextFollowUp || new Date().toISOString().split('T')[0];

        setSubmitting(true);
        const result = await apiCall('followups.php', 'POST', {
            lead_id: selectedTask.lead_id,
            remark: updateRemark,
            status: updateStatus,
            next_follow_up_date: finalDate
        });
        setSubmitting(false);

        if (result.success) {
            showSnackbar('Task updated successfully', 'success');
            setUpdateModalVisible(false);
            fetchTasks(filter);
        } else {
            showSnackbar(result.message || 'Failed to update task', 'error');
        }
    };

    const formatDateTime = (dateStr: string) => {
        if (!dateStr) return '';
        const isoStr = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T');
        const date = new Date(isoStr);
        return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) + ' ' +
            date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
    };

    const renderTask = ({ item }: any) => {
        const isDone = parseInt(item.is_completed) === 1;
        return (
            <TouchableOpacity style={[styles.card, isDone && styles.cardDone]} onPress={() => openUpdateModal(item)}>
                <View style={styles.cardTop}>
                    <View style={styles.leadMain}>
                        <View style={styles.nameRow}>
                            <Text style={styles.leadNameCompact}>{item.lead_name}</Text>
                            {isDone && (
                                <View style={styles.doneBadge}>
                                    <CheckCircle2 size={10} color="#10b981" />
                                    <Text style={styles.doneBadgeText}>DONE</Text>
                                </View>
                            )}
                        </View>
                        <Text style={styles.mobileCompact}>{item.lead_mobile}</Text>
                    </View>
                    <View style={styles.actionRow}>
                        <TouchableOpacity
                            style={[styles.callSmall, isDone && { backgroundColor: '#94a3b8' }]}
                            onPress={() => handleWhatsApp(item.lead_mobile)}
                        >
                            <MessageCircle size={14} color="#fff" />
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={[styles.callSmall, isDone && { backgroundColor: '#94a3b8' }]}
                            onPress={() => handleCall(item.lead_mobile)}
                        >
                            <Phone size={14} color="#fff" fill="#fff" />
                        </TouchableOpacity>
                    </View>
                </View>

                <View style={styles.middleRow}>
                    <View style={[styles.statusMini, { backgroundColor: getStatusColor(item.lead_status) }]}>
                        <Text style={styles.statusMiniText}>{item.lead_status}</Text>
                    </View>
                    <View style={styles.dateMini}>
                        <Clock size={10} color="#6366f1" />
                        <Text style={styles.dateMiniText}>{new Date(item.next_follow_up_date).toLocaleDateString()}</Text>
                    </View>
                </View>

                <View style={styles.remarkCompact}>
                    <MessageSquare size={12} color="#94a3b8" />
                    <Text style={styles.remarkTextCompact} numberOfLines={2}>{item.remark}</Text>
                </View>
            </TouchableOpacity>
        );
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

    return (
        <View style={styles.container}>
            <View style={styles.topFilterContainer}>
                <View style={styles.filterBar}>
                    <TouchableOpacity
                        style={[styles.filterBtn, filter === 'today' && styles.filterBtnActive]}
                        onPress={() => setFilter('today')}
                    >
                        <Text style={[styles.filterBtnText, filter === 'today' && styles.filterBtnTextActive]}>Today's Focus</Text>
                    </TouchableOpacity>
                    <TouchableOpacity
                        style={[styles.filterBtn, filter === 'upcoming' && styles.filterBtnActive]}
                        onPress={() => setFilter('upcoming')}
                    >
                        <Text style={[styles.filterBtnText, filter === 'upcoming' && styles.filterBtnTextActive]}>Upcoming</Text>
                    </TouchableOpacity>
                </View>

                {filter === 'today' && (
                    <View style={styles.subFilterBar}>
                        <TouchableOpacity
                            style={[styles.subFilterBtn, subFilter === 'pending' && styles.subFilterBtnActive]}
                            onPress={() => setSubFilter('pending')}
                        >
                            <Text style={[styles.subFilterText, subFilter === 'pending' && styles.subFilterTextActive]}>
                                Pending ({tasks.filter((t: any) => parseInt(t.is_completed) === 0).length})
                            </Text>
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={[styles.subFilterBtn, subFilter === 'completed' && styles.subFilterBtnActive]}
                            onPress={() => setSubFilter('completed')}
                        >
                            <Text style={[styles.subFilterText, subFilter === 'completed' && styles.subFilterTextActive]}>
                                Done ({tasks.filter((t: any) => parseInt(t.is_completed) === 1).length})
                            </Text>
                        </TouchableOpacity>
                    </View>
                )}
            </View>

            {loading ? (
                <ActivityIndicator size="large" color="#6366f1" style={{ marginTop: 50 }} />
            ) : (
                <FlatList
                    data={filteredTasks}
                    renderItem={renderTask}
                    keyExtractor={(item: any) => item.id.toString()}
                    contentContainerStyle={styles.list}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                    ListEmptyComponent={
                        <View style={styles.empty}>
                            <CalendarIcon size={48} color="#e2e8f0" />
                            <Text style={styles.emptyText}>
                                {filter === 'today'
                                    ? (subFilter === 'pending' ? 'All clear! No pending tasks today.' : 'No tasks completed yet today.')
                                    : 'Nothing scheduled for upcoming days.'}
                            </Text>
                        </View>
                    }
                />
            )}

            {/* Update Task Modal */}
            <Modal
                visible={updateModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setUpdateModalVisible(false)}
            >
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                    style={styles.modalOverlay}
                >
                    <View style={[styles.modalContent, { height: '85%' }]}>
                        <View style={styles.modalHeader}>
                            <View>
                                <Text style={styles.modalTitle}>Process Task</Text>
                                <Text style={styles.modalSubtitle}>{selectedTask?.lead_name}</Text>
                            </View>
                            <TouchableOpacity onPress={() => setUpdateModalVisible(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>

                        {/* Modal Tabs */}
                        <View style={styles.modalTabs}>
                            <TouchableOpacity
                                style={[styles.modalTab, activeTab === 'update' && styles.modalTabActive]}
                                onPress={() => setActiveTab('update')}
                            >
                                <Text style={[styles.modalTabText, activeTab === 'update' && styles.modalTabTextActive]}>Update</Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.modalTab, activeTab === 'history' && styles.modalTabActive]}
                                onPress={() => setActiveTab('history')}
                            >
                                <Text style={[styles.modalTabText, activeTab === 'history' && styles.modalTabTextActive]}>History</Text>
                            </TouchableOpacity>
                        </View>

                        <ScrollView style={styles.modalForm} showsVerticalScrollIndicator={false}>
                            {activeTab === 'update' ? (
                                <View>
                                    <Text style={styles.label}>Update Lead Status</Text>
                                    <View style={styles.statusGrid}>
                                        {STATUS_OPTIONS.map((status) => (
                                            <TouchableOpacity
                                                key={status}
                                                style={[styles.statusItem, updateStatus === status && { backgroundColor: getStatusColor(status) }]}
                                                onPress={() => setUpdateStatus(status)}
                                            >
                                                <Text style={[styles.statusItemText, updateStatus === status && { color: '#fff' }]}>
                                                    {status}
                                                </Text>
                                            </TouchableOpacity>
                                        ))}
                                    </View>

                                    <Text style={styles.label}>Call Remark *</Text>
                                    <TextInput
                                        style={[styles.input, styles.textArea]}
                                        placeholder="Details of your interaction..."
                                        value={updateRemark}
                                        onChangeText={setUpdateRemark}
                                        multiline
                                        numberOfLines={3}
                                    />

                                    <Text style={styles.label}>Next Follow-up Date</Text>
                                    <TouchableOpacity
                                        style={styles.calendarInput}
                                        onPress={() => setShowDatePicker(true)}
                                    >
                                        <CalendarIcon size={20} color="#6366f1" />
                                        <Text style={styles.calendarInputText}>
                                            {nextFollowUp || 'Today (Default)'}
                                        </Text>
                                        <ChevronRight size={18} color="#94a3b8" />
                                    </TouchableOpacity>

                                    {showDatePicker && (
                                        <DateTimePicker
                                            value={nextFollowUp ? new Date(nextFollowUp) : new Date()}
                                            mode="date"
                                            display="default"
                                            onChange={onDateChange}
                                            minimumDate={new Date()}
                                        />
                                    )}

                                    <TouchableOpacity
                                        style={[styles.submitButton, submitting && styles.buttonDisabled]}
                                        onPress={handleUpdateTask}
                                        disabled={submitting}
                                    >
                                        <CheckCircle2 color="#fff" size={20} />
                                        <Text style={styles.submitButtonText}>
                                            {submitting ? 'Saving...' : 'Mark as Completed'}
                                        </Text>
                                    </TouchableOpacity>
                                </View>
                            ) : (
                                <View>
                                    <View style={styles.historySection}>
                                        <View style={styles.sectionHeader}>
                                            <History size={16} color="#6366f1" />
                                            <Text style={styles.sectionTitle}>Follow-up Records</Text>
                                        </View>

                                        {loadingHistory ? (
                                            <ActivityIndicator size="small" color="#6366f1" style={{ marginVertical: 20 }} />
                                        ) : history.length > 0 ? (
                                            <View style={styles.timeline}>
                                                {history.map((h, index) => (
                                                    <View key={h.id} style={styles.timelineItem}>
                                                        <View style={styles.timelineMarker}>
                                                            <View style={styles.timelineDot} />
                                                            {index !== history.length - 1 && <View style={styles.timelineLine} />}
                                                        </View>
                                                        <View style={styles.timelineContent}>
                                                            <View style={styles.timelineHeader}>
                                                                <Text style={styles.timelineDate}>
                                                                    {formatDateTime(h.created_at)}
                                                                </Text>
                                                                {h.next_follow_up_date && (
                                                                    <View style={styles.nextBadge}>
                                                                        <Clock size={10} color="#6366f1" />
                                                                        <Text style={styles.nextDateText}>{h.next_follow_up_date}</Text>
                                                                    </View>
                                                                )}
                                                            </View>
                                                            <Text style={styles.timelineRemark}>{h.remark}</Text>
                                                        </View>
                                                    </View>
                                                ))}
                                            </View>
                                        ) : (
                                            <View style={styles.noHistoryContainer}>
                                                <MessageSquare size={32} color="#cbd5e1" />
                                                <Text style={styles.noHistory}>No previous records found.</Text>
                                            </View>
                                        )}
                                    </View>
                                </View>
                            )}
                        </ScrollView>
                    </View>
                </KeyboardAvoidingView>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#fff',
    },
    filterBar: {
        flexDirection: 'row',
        padding: 12,
        gap: 10,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    filterBtn: {
        flex: 1,
        paddingVertical: 10,
        borderRadius: 8,
        backgroundColor: '#f1f5f9',
        alignItems: 'center',
    },
    filterBtnActive: {
        backgroundColor: '#6366f1',
    },
    filterBtnText: {
        fontSize: 13,
        fontWeight: '700',
        color: '#64748b',
    },
    filterBtnTextActive: {
        color: '#fff',
    },
    topFilterContainer: {
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    subFilterBar: {
        flexDirection: 'row',
        paddingHorizontal: 15,
        paddingBottom: 10,
        gap: 15,
    },
    subFilterBtn: {
        paddingVertical: 6,
        paddingHorizontal: 12,
        borderRadius: 20,
        backgroundColor: '#f8fafc',
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    subFilterBtnActive: {
        backgroundColor: '#ecfdf5',
        borderColor: '#10b981',
    },
    subFilterText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
    },
    subFilterTextActive: {
        color: '#059669',
    },
    list: {
        padding: 12,
    },
    card: {
        backgroundColor: '#fff',
        borderRadius: 16,
        padding: 16,
        marginBottom: 12,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        elevation: 2,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 4,
    },
    cardDone: {
        backgroundColor: '#f8fafc',
        borderColor: '#e2e8f0',
        opacity: 0.85,
    },
    cardTop: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
        marginBottom: 12,
    },
    leadMain: {
        flex: 1,
    },
    actionRow: {
        flexDirection: 'row',
        gap: 8,
    },
    nameRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    doneBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#ecfdf5',
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 4,
        gap: 3,
    },
    doneBadgeText: {
        fontSize: 10,
        fontWeight: '800',
        color: '#10b981',
    },
    leadNameCompact: {
        fontSize: 16,
        fontWeight: '700',
        color: '#1e293b',
    },
    mobileCompact: {
        fontSize: 12,
        color: '#64748b',
        fontWeight: '500',
    },
    callSmall: {
        width: 30,
        height: 30,
        borderRadius: 15,
        backgroundColor: '#10b981',
        justifyContent: 'center',
        alignItems: 'center',
    },
    middleRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        marginBottom: 8,
    },
    statusMini: {
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 4,
    },
    statusMiniText: {
        fontSize: 9,
        fontWeight: '900',
        color: '#fff',
        textTransform: 'uppercase',
    },
    dateMini: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        backgroundColor: '#eff6ff',
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 4,
    },
    dateMiniText: {
        fontSize: 10,
        fontWeight: '700',
        color: '#6366f1',
    },
    remarkCompact: {
        flexDirection: 'row',
        gap: 6,
        backgroundColor: '#f8fafc',
        padding: 8,
        borderRadius: 8,
    },
    remarkTextCompact: {
        flex: 1,
        fontSize: 12,
        color: '#475569',
        lineHeight: 16,
    },
    empty: {
        alignItems: 'center',
        marginTop: 100,
    },
    emptyText: {
        marginTop: 12,
        color: '#94a3b8',
        fontSize: 16,
    },
    // Modal Styles
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: '#fff',
        borderTopLeftRadius: 24,
        borderTopRightRadius: 24,
        maxHeight: '90%',
    },
    modalHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 20,
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    modalTitle: {
        fontSize: 20,
        fontWeight: '800',
        color: '#1e293b',
    },
    modalSubtitle: {
        fontSize: 14,
        color: '#64748b',
        marginTop: 2,
    },
    modalTabs: {
        flexDirection: 'row',
        paddingHorizontal: 20,
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    modalTab: {
        paddingVertical: 14,
        marginRight: 24,
        borderBottomWidth: 2,
        borderBottomColor: 'transparent',
    },
    modalTabActive: {
        borderBottomColor: '#6366f1',
    },
    modalTabText: {
        fontSize: 15,
        fontWeight: '600',
        color: '#94a3b8',
    },
    modalTabTextActive: {
        color: '#6366f1',
        fontWeight: '700',
    },
    modalForm: {
        paddingVertical: 16,
        paddingHorizontal: 20,
    },
    label: {
        fontSize: 14,
        fontWeight: '700',
        color: '#475569',
        marginBottom: 8,
        marginTop: 16,
    },
    calendarInput: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f8fafc',
        borderWidth: 1,
        borderColor: '#e2e8f0',
        borderRadius: 12,
        padding: 14,
        gap: 12,
        marginTop: 4,
    },
    calendarInputText: {
        flex: 1,
        fontSize: 16,
        color: '#1e293b',
        fontWeight: '500',
    },
    input: {
        backgroundColor: '#f8fafc',
        borderWidth: 1,
        borderColor: '#e2e8f0',
        borderRadius: 12,
        padding: 14,
        fontSize: 16,
        color: '#1e293b',
    },
    textArea: {
        height: 100,
        textAlignVertical: 'top',
    },
    statusGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: 8,
    },
    statusItem: {
        paddingHorizontal: 10,
        paddingVertical: 6,
        borderRadius: 6,
        backgroundColor: '#f1f5f9',
    },
    statusItemText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
    },
    submitButton: {
        backgroundColor: '#6366f1',
        height: 56,
        borderRadius: 16,
        flexDirection: 'row',
        justifyContent: 'center',
        alignItems: 'center',
        gap: 10,
        marginTop: 32,
        marginBottom: 40,
    },
    submitButtonText: {
        color: '#fff',
        fontSize: 17,
        fontWeight: '700',
    },
    buttonDisabled: {
        opacity: 0.6,
    },
    // Timeline Styles
    historySection: {
        marginTop: 4,
    },
    sectionHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        marginBottom: 20,
    },
    sectionTitle: {
        fontSize: 15,
        fontWeight: '800',
        color: '#334155',
    },
    timeline: {
        marginLeft: 4,
    },
    timelineItem: {
        flexDirection: 'row',
        gap: 12,
    },
    timelineMarker: {
        alignItems: 'center',
        width: 12,
    },
    timelineDot: {
        width: 10,
        height: 10,
        borderRadius: 5,
        backgroundColor: '#6366f1',
        zIndex: 1,
    },
    timelineLine: {
        width: 2,
        flex: 1,
        backgroundColor: '#e2e8f0',
        marginVertical: -2,
    },
    timelineContent: {
        flex: 1,
        paddingBottom: 24,
    },
    timelineHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 6,
    },
    timelineDate: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
    },
    timelineRemark: {
        fontSize: 14,
        color: '#1e293b',
        lineHeight: 20,
    },
    nextBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        backgroundColor: '#eff6ff',
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 4,
    },
    nextDateText: {
        fontSize: 10,
        color: '#6366f1',
        fontWeight: '700',
    },
    noHistoryContainer: {
        alignItems: 'center',
        justifyContent: 'center',
        marginTop: 60,
        gap: 12,
    },
    noHistory: {
        textAlign: 'center',
        color: '#94a3b8',
        fontSize: 14,
    }
});
