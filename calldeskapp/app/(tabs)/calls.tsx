import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator, Alert, FlatList, RefreshControl, Modal, TextInput, Linking, ScrollView, KeyboardAvoidingView, Platform } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
import { fetchAndSyncCallLogs, checkCallLogPermission, requestCallLogPermission } from '../../services/callLog';
import { apiCall } from '../../services/api';
import { getUser } from '../../services/auth';
import { PhoneCall, RefreshCcw, Info, CheckCircle2, Clock, UserPlus, FileEdit, X, ChevronRight, Phone, MessageSquare, Calendar as CalendarIcon, Flag, ShieldAlert, CheckCircle } from 'lucide-react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useSnackbar } from '../../context/SnackbarContext';

const STATUS_OPTIONS = ['Pending', 'Follow-up', 'Interested', 'Converted', 'Lost'];

export default function CallsSyncScreen() {
    const { showSnackbar } = useSnackbar();
    const [syncing, setSyncing] = useState(false);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [logs, setLogs] = useState<any[]>([]);
    const [activeFilter, setActiveFilter] = useState('All');
    const [filteredLogs, setFilteredLogs] = useState<any[]>([]);
    const [rationaleModalVisible, setRationaleModalVisible] = useState(false);

    // Update Interaction Modal State
    const [updateModalVisible, setUpdateModalVisible] = useState(false);
    const [selectedLog, setSelectedLog] = useState<any>(null);
    const [updateRemark, setUpdateRemark] = useState('');
    const [updateStatus, setUpdateStatus] = useState('Interested');
    const [nextFollowUp, setNextFollowUp] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [showDatePicker, setShowDatePicker] = useState(false);
    const [userRole, setUserRole] = useState('');
    const [executives, setExecutives] = useState<any[]>([]);
    const [selectedExecutive, setSelectedExecutive] = useState('0');
    const [filterDate, setFilterDate] = useState('');
    const [showFilterDatePicker, setShowFilterDatePicker] = useState(false);

    const [editName, setEditName] = useState('');

    // Add Lead Modal State
    const [addLeadModalVisible, setAddLeadModalVisible] = useState(false);
    const [newLeadName, setNewLeadName] = useState('');
    const [newLeadMobile, setNewLeadMobile] = useState('');
    const [addingLead, setAddingLead] = useState(false);

    const [sources, setSources] = useState<any[]>([]);
    const [selectedSource, setSelectedSource] = useState<number | null>(null);

    const fetchLogs = async () => {
        setLoading(true);
        const params = new URLSearchParams();
        if (selectedExecutive !== '0') params.append('executive_id', selectedExecutive);
        if (filterDate) params.append('date', filterDate);

        const result = await apiCall(`call_logs.php?${params.toString()}`, 'POST');
        if (result.success) {
            setLogs(result.data.logs);
        }

        // Fetch executives if admin
        const user = await getUser();
        setUserRole(user?.role || '');
        if (user?.role === 'admin') {
            const execRes = await apiCall('leads.php?action=executives');
            if (execRes.success) setExecutives(execRes.data);
        }

        // Also fetch sources for the add lead modal
        const sourceRes = await apiCall('sources.php');
        if (sourceRes.success) {
            setSources(sourceRes.data);
            if (sourceRes.data.length > 0) setSelectedSource(sourceRes.data[0].id);
        }
        setLoading(false);
    };

    useFocusEffect(
        useCallback(() => {
            fetchLogs();
        }, [selectedExecutive, filterDate])
    );

    useEffect(() => {
        if (!Array.isArray(logs)) {
            setFilteredLogs([]);
            return;
        }
        if (activeFilter === 'All') {
            setFilteredLogs(logs);
        } else {
            setFilteredLogs(logs.filter(log => log.type === activeFilter));
        }
    }, [activeFilter, logs]);

    const onRefresh = async () => {
        setRefreshing(true);
        await fetchLogs();
        setRefreshing(false);
    };

    const handleSync = async () => {
        if (Platform.OS === 'android') {
            const hasPermission = await checkCallLogPermission();
            if (!hasPermission) {
                setRationaleModalVisible(true);
                return;
            }
        }
        performSync();
    };

    const performSync = async () => {
        setSyncing(true);
        const result = await fetchAndSyncCallLogs();
        setSyncing(false);

        if (result.success) {
            showSnackbar(`Successfully synced ${result.data.synced} call logs.`, 'success');
            fetchLogs();
        } else {
            showSnackbar(result.message || 'Make sure you are on Android and granted permissions.', 'error');
        }
    };

    const handleGrantPermission = async () => {
        setRationaleModalVisible(false);
        const granted = await requestCallLogPermission();
        if (granted) {
            performSync();
        } else {
            showSnackbar('Permission is required to sync call logs.', 'error');
        }
    };

    const openAddLeadModal = (log: any) => {
        setNewLeadMobile(log.mobile);
        setNewLeadName(log.caller_name || '');
        setAddLeadModalVisible(true);
    };

    const handleAddLead = async () => {
        if (!newLeadName) {
            showSnackbar('Please enter lead name', 'error');
            return;
        }

        setAddingLead(true);
        const result = await apiCall('leads.php', 'POST', {
            name: newLeadName,
            mobile: newLeadMobile,
            source_id: selectedSource,
            remarks: 'Added from call logs'
        });
        setAddingLead(false);

        if (result.success) {
            showSnackbar('Lead added successfully', 'success');
            setAddLeadModalVisible(false);
            fetchLogs(); // Refresh to link the lead
        } else {
            showSnackbar(result.message || 'Failed to add lead', 'error');
        }
    };

    const openUpdateModal = (log: any) => {
        if (!log.lead_id) {
            openAddLeadModal(log);
            return;
        }
        setSelectedLog(log);
        setEditName(log.lead_name || log.caller_name || '');
        setUpdateStatus(log.lead_status || 'Interested');
        setUpdateRemark('');
        setNextFollowUp('');
        setUpdateModalVisible(true);
    };

    const handleUpdateInteraction = async () => {
        if (!updateRemark) {
            showSnackbar('Please enter a remark', 'error');
            return;
        }

        const finalDate = nextFollowUp || new Date().toISOString().split('T')[0];

        setSubmitting(true);
        const result = await apiCall('followups.php', 'POST', {
            lead_id: selectedLog.lead_id,
            name: editName,
            remark: updateRemark,
            status: updateStatus,
            next_follow_up_date: finalDate
        });
        setSubmitting(false);

        if (result.success) {
            showSnackbar('Interaction recorded successfully', 'success');
            setUpdateModalVisible(false);
            fetchLogs();
        } else {
            showSnackbar(result.message || 'Failed to update lead', 'error');
        }
    };

    const onDateChange = (event: any, selectedDate?: Date) => {
        setShowDatePicker(false);
        if (selectedDate) {
            setNextFollowUp(selectedDate.toISOString().split('T')[0]);
        }
    };

    const handleCallLogLongPress = (log: any) => {
        if (!log.lead_id) return;

        if (userRole !== 'admin' && userRole !== 'owner') return;

        Alert.alert(
            "Lead Actions",
            `Manage lead: ${log.lead_name || log.mobile}`,
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Assign to Executive",
                    onPress: () => {
                        // We need a way to select executive. 
                        // For now let's just use a simple prompt if possible or another modal.
                        // I'll reuse the existing logic if I can.
                        showSnackbar('Please use the Leads screen for detailed assignment.', 'info');
                    }
                },
                {
                    text: "Delete Lead",
                    style: "destructive",
                    onPress: () => handleDeleteLead(log.lead_id, log.lead_name)
                }
            ]
        );
    };

    const handleDeleteLead = async (leadId: number, name: string) => {
        Alert.alert(
            "Confirm Delete",
            `Are you sure you want to delete lead ${name}?`,
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Delete",
                    style: "destructive",
                    onPress: async () => {
                        const res = await apiCall(`leads.php?id=${leadId}`, 'DELETE');
                        if (res.success) {
                            showSnackbar('Lead deleted successfully', 'success');
                            fetchLogs();
                        } else {
                            showSnackbar(res.message || 'Failed to delete lead', 'error');
                        }
                    }
                }
            ]
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

    const formatTime = (timeStr: string) => {
        if (!timeStr) return '';
        // Convert 'YYYY-MM-DD HH:MM:SS' to 'YYYY-MM-DDTHH:MM:SS' for reliable parsing
        const isoStr = timeStr.includes('T') ? timeStr : timeStr.replace(' ', 'T');
        const date = new Date(isoStr);
        return date.toLocaleTimeString('en-IN', {
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    };

    const formatDate = (timeStr: string) => {
        if (!timeStr) return '';
        const isoStr = timeStr.includes('T') ? timeStr : timeStr.replace(' ', 'T');
        const date = new Date(isoStr);
        return date.toLocaleDateString('en-IN', {
            day: '2-digit',
            month: 'short'
        });
    };

    const getCallTypeStyles = (type: string) => {
        switch (type) {
            case 'Incoming': return { color: '#10b981', bg: '#ecfdf5', icon: <PhoneCall size={14} color="#10b981" /> };
            case 'Outgoing': return { color: '#6366f1', bg: '#f5f3ff', icon: <Phone size={14} color="#6366f1" /> };
            case 'Missed': return { color: '#ef4444', bg: '#fef2f2', icon: <Phone size={14} color="#ef4444" style={{ transform: [{ rotate: '135deg' }] }} /> };
            default: return { color: '#64748b', bg: '#f8fafc', icon: <Phone size={14} color="#64748b" /> };
        }
    };

    const renderLogItem = ({ item }: { item: any }) => {
        const typeStyle = getCallTypeStyles(item.type);
        return (
            <TouchableOpacity
                activeOpacity={0.7}
                onLongPress={() => {
                    if (item.lead_id) {
                        setSelectedLog(item);
                        // Using a dummy lead object for compatibility with handleLongPress if needed
                        // or just show an alert/modal here.
                        // For now let's just use the same logic as Leads screen for consistency
                        handleCallLogLongPress(item);
                    }
                }}
                style={styles.logCard}
            >
                <View style={styles.logHeader}>
                    <View style={[styles.typeIndicator, { backgroundColor: typeStyle.color }]} />
                    <View style={styles.logMainInfo}>
                        <View style={styles.nameRow}>
                            <Text style={styles.logName} numberOfLines={1}>{item.lead_name || item.caller_name || item.mobile}</Text>
                            {item.lead_status && (
                                <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.lead_status) + '20' }]}>
                                    <Text style={[styles.statusBadgeText, { color: getStatusColor(item.lead_status) }]}>{item.lead_status}</Text>
                                </View>
                            )}
                        </View>
                        <View style={styles.typeRow}>
                            {typeStyle.icon}
                            <Text style={[styles.logMeta, { color: typeStyle.color }]}>{item.type} • {item.duration}s</Text>
                        </View>
                    </View>
                    <View style={styles.logTimeInfo}>
                        <Text style={styles.logTime}>{formatTime(item.call_time)}</Text>
                        <Text style={styles.logDate}>{formatDate(item.call_time)}</Text>
                    </View>
                </View>

                <View style={styles.logActions}>
                    {item.lead_id ? (
                        <TouchableOpacity style={styles.updateBtn} onPress={() => openUpdateModal(item)}>
                            <FileEdit size={14} color="#6366f1" />
                            <Text style={styles.updateBtnText}>Update Interaction</Text>
                        </TouchableOpacity>
                    ) : (
                        <TouchableOpacity style={styles.addLeadCardBtn} onPress={() => openAddLeadModal(item)}>
                            <UserPlus size={14} color="#6366f1" />
                            <Text style={styles.addLeadCardBtnText}>Add as Lead</Text>
                        </TouchableOpacity>
                    )}

                    <TouchableOpacity style={styles.callIconBtn} onPress={() => Linking.openURL(`tel:${item.mobile}`)}>
                        <Phone size={16} color="#475569" />
                    </TouchableOpacity>
                </View>
            </TouchableOpacity>
        );
    };

    return (
        <View style={styles.container}>
            {/* Sync Header Section */}
            <View style={styles.syncHeader}>
                <View style={styles.headerTop}>
                    <Text style={styles.headerTitle}>Call Synchronization</Text>
                    <TouchableOpacity
                        style={[styles.miniSyncBtn, syncing && { opacity: 0.5 }]}
                        onPress={handleSync}
                        disabled={syncing}
                    >
                        {syncing ? <ActivityIndicator size="small" color="#fff" /> : <RefreshCcw size={16} color="#fff" />}
                        <Text style={styles.miniSyncBtnText}>Sync Logs</Text>
                    </TouchableOpacity>
                </View>

                {userRole === 'admin' && (
                    <View style={styles.adminFilters}>
                        <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.execFilterBar}>
                            <TouchableOpacity
                                style={[styles.execBadge, selectedExecutive === '0' && styles.execBadgeActive]}
                                onPress={() => setSelectedExecutive('0')}
                            >
                                <Text style={[styles.execBadgeText, selectedExecutive === '0' && styles.execBadgeTextActive]}>All Team</Text>
                            </TouchableOpacity>
                            {executives.map(exec => (
                                <TouchableOpacity
                                    key={exec.id}
                                    style={[styles.execBadge, selectedExecutive === exec.id.toString() && styles.execBadgeActive]}
                                    onPress={() => setSelectedExecutive(exec.id.toString())}
                                >
                                    <Text style={[styles.execBadgeText, selectedExecutive === exec.id.toString() && styles.execBadgeTextActive]}>{exec.name}</Text>
                                </TouchableOpacity>
                            ))}
                        </ScrollView>

                        <View style={styles.dateFilterRow}>
                            <TouchableOpacity
                                style={[styles.dateFilterBtn, filterDate && styles.dateFilterBtnActive]}
                                onPress={() => setShowFilterDatePicker(true)}
                            >
                                <CalendarIcon size={14} color={filterDate ? "#fff" : "#6366f1"} />
                                <Text style={[styles.dateFilterText, filterDate && { color: '#fff' }]}>
                                    {filterDate || 'Filter by Date'}
                                </Text>
                            </TouchableOpacity>
                            {filterDate ? (
                                <TouchableOpacity onPress={() => setFilterDate('')}>
                                    <X size={18} color="#ef4444" />
                                </TouchableOpacity>
                            ) : null}
                        </View>

                        {showFilterDatePicker && (
                            <DateTimePicker
                                value={filterDate ? new Date(filterDate) : new Date()}
                                mode="date"
                                display="default"
                                onChange={(event, date) => {
                                    setShowFilterDatePicker(false);
                                    if (date) setFilterDate(date.toISOString().split('T')[0]);
                                }}
                            />
                        )}
                    </View>
                )}

                <ScrollView
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={styles.filterBar}
                >
                    {['All', 'Incoming', 'Outgoing', 'Missed'].map(filter => (
                        <TouchableOpacity
                            key={filter}
                            style={[
                                styles.filterBadge,
                                activeFilter === filter && styles.filterBadgeActive
                            ]}
                            onPress={() => setActiveFilter(filter)}
                        >
                            <Text style={[
                                styles.filterBadgeText,
                                activeFilter === filter && styles.filterBadgeTextActive
                            ]}>
                                {filter}
                            </Text>
                            {logs.length > 0 && (
                                <View style={[
                                    styles.countBadge,
                                    activeFilter === filter && styles.countBadgeActive
                                ]}>
                                    <Text style={[
                                        styles.countText,
                                        activeFilter === filter && styles.countTextActive
                                    ]}>
                                        {filter === 'All' ? logs.length : logs.filter(l => l.type === filter).length}
                                    </Text>
                                </View>
                            )}
                        </TouchableOpacity>
                    ))}
                </ScrollView>
            </View>

            {loading && !refreshing ? (
                <View style={styles.center}>
                    <ActivityIndicator size="large" color="#6366f1" />
                </View>
            ) : (
                <FlatList
                    data={filteredLogs}
                    renderItem={renderLogItem}
                    keyExtractor={(item) => item.id.toString()}
                    contentContainerStyle={styles.listContent}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
                    }
                    ListEmptyComponent={
                        <View style={styles.emptyState}>
                            {activeFilter === 'All' ? (
                                <>
                                    <PhoneCall size={48} color="#cbd5e1" />
                                    <Text style={styles.emptyText}>No recent call logs found</Text>
                                    <Text style={styles.emptySubText}>Tap Sync to fetch from device</Text>
                                </>
                            ) : (
                                <>
                                    <PhoneCall size={48} color="#cbd5e1" />
                                    <Text style={styles.emptyText}>No {activeFilter} calls</Text>
                                    <Text style={styles.emptySubText}>Try a different filter</Text>
                                </>
                            )}
                        </View>
                    }
                />
            )}

            {/* Interaction Modal */}
            <Modal visible={updateModalVisible} animationType="slide" transparent>
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                    style={styles.modalOverlay}
                >
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Update Interaction</Text>
                            <TouchableOpacity onPress={() => setUpdateModalVisible(false)} style={styles.closeBtn}>
                                <X size={20} color="#64748b" />
                            </TouchableOpacity>
                        </View>

                        <ScrollView style={styles.modalBody}>
                            <View style={styles.leadShortInfo}>
                                <View>
                                    <Text style={styles.infoLabel}>Mobile Number</Text>
                                    <Text style={styles.infoVal}>{selectedLog?.mobile}</Text>
                                </View>
                            </View>

                            <Text style={styles.label}>Lead Name</Text>
                            <TextInput
                                style={styles.input}
                                placeholder="Edit lead name"
                                value={editName}
                                onChangeText={setEditName}
                            />

                            <Text style={styles.label}>Execution Status</Text>
                            <View style={styles.statusGrid}>
                                {STATUS_OPTIONS.map((status) => (
                                    <TouchableOpacity
                                        key={status}
                                        style={[
                                            styles.statusBtn,
                                            updateStatus === status && { backgroundColor: getStatusColor(status), borderColor: getStatusColor(status) }
                                        ]}
                                        onPress={() => setUpdateStatus(status)}
                                    >
                                        <Text style={[styles.statusBtnText, updateStatus === status && { color: '#fff' }]}>{status}</Text>
                                    </TouchableOpacity>
                                ))}
                            </View>

                            <Text style={styles.label}>Call Remark / Summary</Text>
                            <TextInput
                                style={[styles.input, styles.textArea]}
                                placeholder="What was discussed?"
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
                                style={[styles.submitBtn, submitting && { opacity: 0.7 }]}
                                onPress={handleUpdateInteraction}
                                disabled={submitting}
                            >
                                {submitting ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitBtnText}>Save Interaction</Text>}
                            </TouchableOpacity>
                        </ScrollView>
                    </View>
                </KeyboardAvoidingView>
            </Modal>

            {/* Add Lead Modal */}
            <Modal visible={addLeadModalVisible} animationType="slide" transparent>
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                    style={styles.modalOverlay}
                >
                    <View style={[styles.modalContent, { height: '70%' }]}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Add New Lead</Text>
                            <TouchableOpacity onPress={() => setAddLeadModalVisible(false)} style={styles.closeBtn}>
                                <X size={20} color="#64748b" />
                            </TouchableOpacity>
                        </View>

                        <ScrollView style={styles.modalBody}>
                            <Text style={styles.label}>Mobile Number</Text>
                            <TextInput
                                style={[styles.input, { backgroundColor: '#f1f5f9', color: '#64748b' }]}
                                value={newLeadMobile}
                                editable={false}
                            />

                            <Text style={styles.label}>Lead Name</Text>
                            <TextInput
                                style={styles.input}
                                placeholder="Enter full name"
                                value={newLeadName}
                                onChangeText={setNewLeadName}
                                autoFocus
                            />

                            <Text style={styles.label}>Lead Source</Text>
                            <View style={styles.statusGrid}>
                                {Array.isArray(sources) && sources.map((source) => {
                                    if (!source || !source.id) return null;
                                    return (
                                        <TouchableOpacity
                                            key={source.id}
                                            style={[
                                                styles.statusBtn,
                                                selectedSource === source.id && { backgroundColor: '#6366f1', borderColor: '#6366f1' }
                                            ]}
                                            onPress={() => setSelectedSource(source.id)}
                                        >
                                            <Text style={[styles.statusBtnText, selectedSource === source.id && { color: '#fff' }]}>{source.source_name}</Text>
                                        </TouchableOpacity>
                                    );
                                })}
                            </View>

                            <TouchableOpacity
                                style={[styles.submitBtn, addingLead && { opacity: 0.7 }]}
                                onPress={handleAddLead}
                                disabled={addingLead}
                            >
                                {addingLead ? <ActivityIndicator color="#fff" /> : <Text style={styles.submitBtnText}>Create Lead</Text>}
                            </TouchableOpacity>
                        </ScrollView>
                    </View>
                </KeyboardAvoidingView>
            </Modal>

            {/* Permission Rationale Modal */}
            <Modal visible={rationaleModalVisible} animationType="fade" transparent>
                <View style={styles.modalOverlayCenter}>
                    <View style={styles.rationaleContent}>
                        <View style={styles.rationaleHeader}>
                            <View style={styles.shieldIconContainer}>
                                <ShieldAlert size={32} color="#6366f1" />
                            </View>
                            <Text style={styles.rationaleTitle}>Call Log Access Required</Text>
                        </View>

                        <View style={styles.rationaleBody}>
                            <Text style={styles.rationaleText}>
                                To sync your business calls and manage your pipeline effectively, we need permission to access your device's Call Logs and Contacts.
                            </Text>

                            <View style={styles.featureItem}>
                                <CheckCircle size={16} color="#10b981" />
                                <Text style={styles.featureText}>Automatically track client calls</Text>
                            </View>
                            <View style={styles.featureItem}>
                                <CheckCircle size={16} color="#10b981" />
                                <Text style={styles.featureText}>Record call duration and timing</Text>
                            </View>
                            <View style={styles.featureItem}>
                                <CheckCircle size={16} color="#10b981" />
                                <Text style={styles.featureText}>Quickly add new leads from recent calls</Text>
                            </View>

                            <Text style={styles.privacyNote}>
                                We only read call details (number, duration) and do not share this data with third parties. You can revoke this permission anytime in settings.
                            </Text>
                        </View>

                        <View style={styles.rationaleFooter}>
                            <TouchableOpacity
                                style={styles.cancelLink}
                                onPress={() => setRationaleModalVisible(false)}
                            >
                                <Text style={styles.cancelLinkText}>Not Now</Text>
                            </TouchableOpacity>

                            <TouchableOpacity
                                style={styles.grantBtn}
                                onPress={handleGrantPermission}
                            >
                                <Text style={styles.grantBtnText}>Grant Permission</Text>
                            </TouchableOpacity>
                        </View>
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
    syncHeader: {
        backgroundColor: '#fff',
        padding: 16,
        paddingTop: 12,
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    headerTop: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    headerTitle: {
        fontSize: 16,
        fontWeight: '800',
        color: '#0f172a',
    },
    miniSyncBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#6366f1',
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 8,
        gap: 6,
    },
    miniSyncBtnText: {
        color: '#fff',
        fontSize: 12,
        fontWeight: '700',
    },
    infoStrip: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        marginTop: 10,
        backgroundColor: '#f5f3ff',
        padding: 6,
        borderRadius: 6,
    },
    infoStripText: {
        fontSize: 11,
        color: '#6366f1',
        fontWeight: '600',
    },
    center: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    listContent: {
        padding: 16,
    },
    logCard: {
        backgroundColor: '#fff',
        borderRadius: 12,
        marginBottom: 12,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        overflow: 'hidden',
    },
    logHeader: {
        flexDirection: 'row',
        padding: 12,
        alignItems: 'center',
    },
    typeIndicator: {
        width: 4,
        height: 32,
        borderRadius: 2,
        marginRight: 12,
    },
    logMainInfo: {
        flex: 1,
    },
    logName: {
        fontSize: 15,
        fontWeight: '700',
        color: '#1e293b',
    },
    logMeta: {
        fontSize: 12,
        color: '#64748b',
        marginTop: 2,
    },
    logTimeInfo: {
        alignItems: 'flex-end',
    },
    logTime: {
        fontSize: 13,
        fontWeight: '700',
        color: '#0f172a',
    },
    logDate: {
        fontSize: 11,
        color: '#94a3b8',
        marginTop: 1,
    },
    logActions: {
        flexDirection: 'row',
        borderTopWidth: 1,
        borderTopColor: '#f8fafc',
        padding: 8,
        alignItems: 'center',
        justifyContent: 'space-between',
    },
    updateBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        paddingHorizontal: 8,
    },
    updateBtnText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#6366f1',
    },
    addLeadCardBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
        paddingHorizontal: 8,
    },
    addLeadCardBtnText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#6366f1',
    },
    callIconBtn: {
        padding: 6,
        backgroundColor: '#f1f5f9',
        borderRadius: 8,
    },
    emptyState: {
        alignItems: 'center',
        marginTop: 60,
    },
    emptyText: {
        fontSize: 16,
        fontWeight: '700',
        color: '#64748b',
        marginTop: 16,
    },
    emptySubText: {
        fontSize: 13,
        color: '#94a3b8',
        marginTop: 4,
    },
    filterBar: {
        flexDirection: 'row',
        paddingVertical: 12,
        gap: 10,
    },
    filterBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 12,
        paddingVertical: 6,
        borderRadius: 20,
        backgroundColor: '#f1f5f9',
        borderWidth: 1,
        borderColor: '#e2e8f0',
        gap: 6,
    },
    filterBadgeActive: {
        backgroundColor: '#6366f1',
        borderColor: '#6366f1',
    },
    filterBadgeText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
    },
    filterBadgeTextActive: {
        color: '#fff',
    },
    countBadge: {
        backgroundColor: '#e2e8f0',
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 10,
        minWidth: 20,
        alignItems: 'center',
    },
    countBadgeActive: {
        backgroundColor: 'rgba(255,255,255,0.2)',
    },
    countText: {
        fontSize: 10,
        fontWeight: '800',
        color: '#64748b',
    },
    countTextActive: {
        color: '#fff',
    },
    nameRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    statusBadge: {
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 4,
    },
    statusBadgeText: {
        fontSize: 10,
        fontWeight: '800',
    },
    typeRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        marginTop: 2,
    },
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: '#fff',
        borderTopLeftRadius: 24,
        borderTopRightRadius: 24,
        height: '80%',
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
        fontSize: 18,
        fontWeight: '800',
        color: '#1e293b',
    },
    closeBtn: {
        padding: 4,
    },
    modalBody: {
        padding: 20,
    },
    leadShortInfo: {
        backgroundColor: '#f8fafc',
        padding: 12,
        borderRadius: 12,
        marginBottom: 20,
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    infoLabel: {
        fontSize: 13,
        color: '#64748b',
    },
    infoVal: {
        fontSize: 14,
        fontWeight: '700',
        color: '#0f172a',
    },
    label: {
        fontSize: 14,
        fontWeight: '700',
        color: '#475569',
        marginBottom: 8,
        marginTop: 16,
    },
    statusGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: 8,
        marginBottom: 8,
    },
    statusBtn: {
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 20,
        backgroundColor: '#f1f5f9',
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    statusBtnText: {
        fontSize: 12,
        fontWeight: '600',
        color: '#64748b',
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
        height: 80,
        textAlignVertical: 'top',
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
    },
    calendarInputText: {
        flex: 1,
        fontSize: 16,
        color: '#1e293b',
        fontWeight: '500',
    },
    submitBtn: {
        backgroundColor: '#6366f1',
        height: 56,
        borderRadius: 16,
        justifyContent: 'center',
        alignItems: 'center',
        marginTop: 32,
        marginBottom: 40,
    },
    submitBtnText: {
        color: '#fff',
        fontSize: 16,
        fontWeight: '800',
    },
    adminFilters: {
        marginTop: 16,
        gap: 12,
    },
    execFilterBar: {
        paddingBottom: 4,
        gap: 8,
    },
    execBadge: {
        paddingHorizontal: 12,
        paddingVertical: 6,
        borderRadius: 20,
        backgroundColor: '#f1f5f9',
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    execBadgeActive: {
        backgroundColor: '#6366f1',
        borderColor: '#6366f1',
    },
    execBadgeText: {
        fontSize: 12,
        fontWeight: '700',
        color: '#64748b',
    },
    execBadgeTextActive: {
        color: '#fff',
    },
    dateFilterRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 12,
    },
    dateFilterBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f8fafc',
        borderWidth: 1,
        borderColor: '#e2e8f0',
        paddingHorizontal: 16,
        paddingVertical: 10,
        borderRadius: 12,
        gap: 10,
        flex: 1,
    },
    dateFilterBtnActive: {
        backgroundColor: '#6366f1',
        borderColor: '#6366f1',
    },
    dateFilterText: {
        fontSize: 14,
        fontWeight: '700',
        color: '#64748b',
    },
    // Rationale Modal Styles
    modalOverlayCenter: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'center',
        alignItems: 'center',
    },
    rationaleContent: {
        width: '85%',
        backgroundColor: '#fff',
        borderRadius: 24,
        padding: 24,
        alignItems: 'center',
        shadowColor: "#000",
        shadowOffset: { width: 0, height: 10 },
        shadowOpacity: 0.1,
        shadowRadius: 20,
        elevation: 10,
    },
    rationaleHeader: {
        alignItems: 'center',
        marginBottom: 20,
    },
    shieldIconContainer: {
        width: 64,
        height: 64,
        borderRadius: 32,
        backgroundColor: '#f5f3ff',
        justifyContent: 'center',
        alignItems: 'center',
        marginBottom: 16,
    },
    rationaleTitle: {
        fontSize: 20,
        fontWeight: '800',
        color: '#0f172a',
        textAlign: 'center',
    },
    rationaleBody: {
        width: '100%',
        marginBottom: 24,
    },
    rationaleText: {
        fontSize: 15,
        color: '#475569',
        textAlign: 'center',
        lineHeight: 22,
        marginBottom: 20,
    },
    featureItem: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 10,
        marginBottom: 12,
        backgroundColor: '#f8fafc',
        padding: 10,
        borderRadius: 12,
    },
    featureText: {
        fontSize: 14,
        color: '#1e293b',
        fontWeight: '600',
    },
    privacyNote: {
        fontSize: 12,
        color: '#94a3b8',
        textAlign: 'center',
        marginTop: 16,
        fontStyle: 'italic',
    },
    rationaleFooter: {
        flexDirection: 'row',
        width: '100%',
        justifyContent: 'space-between',
        alignItems: 'center',
        gap: 12,
    },
    cancelLink: {
        flex: 1,
        height: 48,
        justifyContent: 'center',
        alignItems: 'center',
    },
    cancelLinkText: {
        fontSize: 15,
        color: '#64748b',
        fontWeight: '700',
    },
    grantBtn: {
        flex: 2,
        height: 48,
        backgroundColor: '#6366f1',
        borderRadius: 12,
        justifyContent: 'center',
        alignItems: 'center',
    },
    grantBtnText: {
        fontSize: 15,
        color: '#fff',
        fontWeight: '700',
    }
});
