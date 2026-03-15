import React, { useEffect, useState, useCallback } from 'react';
import { View, Text, FlatList, StyleSheet, ActivityIndicator, RefreshControl, TouchableOpacity, Linking, Modal, TextInput, Alert, ScrollView, Platform, KeyboardAvoidingView, TouchableWithoutFeedback } from 'react-native';
import { makeCall } from '../../services/dialer';
import { useFocusEffect } from '@react-navigation/native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { apiCall } from '../../services/api';
import { getUser } from '../../services/auth';
import { Phone, User, Tag, Plus, X, ChevronRight, CheckCircle2, History, MessageSquare, Calendar as CalendarIcon, Search, Filter, MessageCircle, MoreVertical, Trash2, UserPlus } from 'lucide-react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useSnackbar } from '../../context/SnackbarContext';
import * as SecureStore from 'expo-secure-store';
import { TOKEN_KEY, BASE_URL } from '../../constants/Config';
import { Audio } from 'expo-av';
import { Play, Pause } from 'lucide-react-native';

const STATUS_OPTIONS = ['Pending', 'Follow-up', 'Interested', 'Converted', 'Lost'];

export default function LeadsScreen() {
    const { showSnackbar } = useSnackbar();
    const insets = useSafeAreaInsets();
    const [leads, setLeads] = useState([]);
    const [sources, setSources] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('All');
    const [whatsappMessages, setWhatsappMessages] = useState<any[]>([]);
    const [executives, setExecutives] = useState<any[]>([]);
    const [userRole, setUserRole] = useState<string>('');

    // Action Modal State (Long Press)
    const [actionModalVisible, setActionModalVisible] = useState(false);
    const [assignModalVisible, setAssignModalVisible] = useState(false);
    const [selectedLeadForAction, setSelectedLeadForAction] = useState<any>(null);

    // Add Lead Modal State
    const [addModalVisible, setAddModalVisible] = useState(false);
    const [newName, setNewName] = useState('');
    const [newMobile, setNewMobile] = useState('');
    const [newSource, setNewSource] = useState('0');
    const [newRemarks, setNewRemarks] = useState('');
    const [submitting, setSubmitting] = useState(false);

    // Update Lead Modal State
    const [updateModalVisible, setUpdateModalVisible] = useState(false);
    const [selectedLead, setSelectedLead] = useState<any>(null);
    const [updateRemark, setUpdateRemark] = useState('');
    const [updateStatus, setUpdateStatus] = useState('');
    const [nextFollowUp, setNextFollowUp] = useState('');
    const [activeTab, setActiveTab] = useState<'update' | 'history'>('update');
    const [history, setHistory] = useState<any[]>([]);
    const [recordings, setRecordings] = useState<any[]>([]);
    const [loadingHistory, setLoadingHistory] = useState(false);

    // Audio Playback State
    const [sound, setSound] = useState<Audio.Sound | null>(null);
    const [playingId, setPlayingId] = useState<number | null>(null);
    const [playbackStatus, setPlaybackStatus] = useState<any>(null);

    // Date Picker State
    const [showDatePicker, setShowDatePicker] = useState(false);

    const fetchData = async () => {
        const [leadsRes, sourcesRes, waRes] = await Promise.all([
            apiCall('leads.php'),
            apiCall('sources.php'),
            apiCall('whatsapp_messages.php')
        ]);

        if (leadsRes.success) setLeads(leadsRes.data);
        if (sourcesRes.success) setSources(sourcesRes.data);
        if (waRes.success) setWhatsappMessages(waRes.data);

        // Fetch executives if admin
        const execRes = await apiCall('leads.php?action=executives');
        if (execRes.success) setExecutives(execRes.data);

        setLoading(false);
    };

    const fetchUserRole = async () => {
        const user = await getUser();
        if (user) {
            // Ensure lowercase for consistent comparison
            setUserRole(user.role?.toLowerCase() || '');
        }
    };

    useEffect(() => {
        fetchUserRole();
    }, []);

    const fetchHistory = async (leadId: number, mobile: string) => {
        setLoadingHistory(true);
        const [histRes, recRes] = await Promise.all([
            apiCall(`history.php?lead_id=${leadId}`),
            apiCall(`call_logs.php?search=${mobile}`, 'POST')
        ]);
        
        if (histRes.success) setHistory(histRes.data);
        if (recRes.success) {
            // Only keep logs with recording paths
            setRecordings(recRes.data.logs.filter((l: any) => l.recording_path));
        }
        setLoadingHistory(false);
    };

    const handleToggleAudio = async (item: any) => {
        try {
            if (playingId === item.id) {
                if (playbackStatus?.isPlaying) {
                    await sound?.pauseAsync();
                } else {
                    await sound?.playAsync();
                }
                return;
            }

            if (sound) await sound.unloadAsync();

            const audioUrl = BASE_URL.replace('/api', '') + '/' + item.recording_path;
            const { sound: newSound } = await Audio.Sound.createAsync(
                { uri: audioUrl },
                { shouldPlay: true },
                (status) => {
                    setPlaybackStatus(status);
                    if ((status as any).didJustFinish) setPlayingId(null);
                }
            );
            setSound(newSound);
            setPlayingId(item.id);
        } catch (error) {
            showSnackbar('Playback error', 'error');
        }
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

    const filteredLeads = Array.isArray(leads) ? leads.filter((lead: any) => {
        if (!lead || !lead.name) return false;
        const matchesSearch = lead.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
            (lead.mobile && lead.mobile.includes(searchQuery));
        const matchesStatus = statusFilter === 'All' || lead.status === statusFilter;
        return matchesSearch && matchesStatus;
    }) : [];

    const handleAddLead = async () => {
        if (!newName || !newMobile) {
            showSnackbar('Name and Mobile are required', 'error');
            return;
        }

        setSubmitting(true);
        const result = await apiCall('leads.php', 'POST', {
            name: newName,
            mobile: newMobile,
            source_id: newSource,
            remarks: newRemarks
        });
        setSubmitting(false);

        if (result.success) {
            showSnackbar('Lead added successfully', 'success');
            setAddModalVisible(false);
            resetAddForm();
            fetchData();
        } else {
            showSnackbar(result.message || 'Failed to add lead', 'error');
        }
    };

    const resetAddForm = () => {
        setNewName('');
        setNewMobile('');
        setNewSource('0');
        setNewRemarks('');
    };

    const openUpdateModal = (lead: any) => {
        if (!lead) return;
        setSelectedLead(lead);
        setUpdateStatus(lead.status || 'Pending');
        setUpdateRemark('');
        setNextFollowUp('');
        setHistory([]);
        setActiveTab('update');
        setUpdateModalVisible(true);
        if (lead.id) fetchHistory(lead.id, lead.mobile);
    };

    const handleLongPress = (lead: any) => {
        setSelectedLeadForAction(lead);
        setActionModalVisible(true);
    };

    const handleDeleteLead = async () => {
        const lead = selectedLeadForAction || selectedLead;
        if (!lead) return;

        Alert.alert(
            "Delete Lead",
            `Are you sure you want to delete ${lead.name}?`,
            [
                { text: "Cancel", style: "cancel" },
                {
                    text: "Delete",
                    style: "destructive",
                    onPress: async () => {
                        const result = await apiCall(`leads.php?id=${lead.id}`, 'DELETE');
                        if (result.success) {
                            showSnackbar('Lead deleted', 'success');
                            setActionModalVisible(false);
                            setUpdateModalVisible(false);
                            fetchData();
                        } else {
                            showSnackbar(result.message || 'Failed to delete lead', 'error');
                        }
                    }
                }
            ]
        );
    };

    const handleAssignLead = async (executiveId: number) => {
        if (!selectedLeadForAction || !selectedLeadForAction.id) return;
        setSubmitting(true);
        const result = await apiCall('assign.php', 'POST', {
            lead_id: selectedLeadForAction.id,
            assign_to: executiveId
        });
        setSubmitting(false);

        if (result.success) {
            showSnackbar('Lead assigned successfully', 'success');
            setAssignModalVisible(false);
            setActionModalVisible(false);
            fetchData();
        } else {
            showSnackbar(result.message || 'Failed to assign lead', 'error');
        }
    };

    const handleUpdateLead = async () => {
        if (!updateRemark) {
            showSnackbar('Please enter a remark', 'error');
            return;
        }

        const finalDate = nextFollowUp || new Date().toISOString().split('T')[0];

        setSubmitting(true);
        const result = await apiCall('followups.php', 'POST', {
            lead_id: selectedLead?.id,
            remark: updateRemark,
            status: updateStatus,
            next_follow_up_date: finalDate
        });
        setSubmitting(false);

        if (result.success) {
            showSnackbar('Lead updated successfully', 'success');
            setUpdateModalVisible(false);
            fetchData();
        } else {
            showSnackbar(result.message || 'Failed to update lead', 'error');
        }
    };

    const formatDateTime = (dateStr: string) => {
        if (!dateStr) return '';
        const isoStr = dateStr.includes('T') ? dateStr : dateStr.replace(' ', 'T');
        const date = new Date(isoStr);
        return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' }) + ' ' +
            date.toLocaleTimeString('en-IN', { hour: '2-digit', minute: '2-digit', hour12: true });
    };

    const renderLead = ({ item }: any) => (
        <TouchableOpacity
            style={styles.card}
            onPress={() => openUpdateModal(item)}
            onLongPress={() => handleLongPress(item)}
            delayLongPress={500}
        >
            <View style={styles.cardContent}>
                <View style={styles.avatarCompact}>
                    <Text style={styles.avatarTextCompact}>{item.name.charAt(0).toUpperCase()}</Text>
                </View>
                <View style={styles.cardInfo}>
                    <Text style={styles.leadName} numberOfLines={1}>{item.name}</Text>
                    <Text style={styles.mobileTextCompact}>{item.mobile}</Text>
                </View>
                <View style={styles.itemRight}>
                    <View style={[styles.statusBadgeCompact, { backgroundColor: getStatusColor(item.status) }]}>
                        <Text style={styles.statusTextCompact}>{item.status}</Text>
                    </View>
                    <View style={styles.actionRowLead}>
                        <TouchableOpacity
                            style={styles.whatsappCircleCompact}
                            onPress={() => handleWhatsApp(item.mobile)}
                        >
                            <MessageCircle size={14} color="#fff" />
                        </TouchableOpacity>
                        <TouchableOpacity
                            style={styles.callCircleCompact}
                            onPress={() => handleCall(item.mobile)}
                        >
                            <Phone size={14} color="#fff" fill="#fff" />
                        </TouchableOpacity>
                    </View>
                </View>
            </View>
        </TouchableOpacity>
    );

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'Converted': return '#10b981';
            case 'Interested': return '#6366f1';
            case 'Lost': return '#ef4444';
            case 'Follow-up': return '#f59e0b';
            default: return '#94a3b8';
        }
    };

    if (loading) {
        return (
            <View style={styles.center}>
                <ActivityIndicator size="large" color="#6366f1" />
            </View>
        );
    }

    return (
        <View style={styles.container}>
            {/* Search and Quick Filters */}
            <View style={styles.topTools}>
                <View style={styles.searchBar}>
                    <Search size={18} color="#94a3b8" />
                    <TextInput
                        style={styles.searchInput}
                        placeholder="Search name or mobile..."
                        value={searchQuery}
                        onChangeText={setSearchQuery}
                    />
                    {searchQuery.length > 0 && (
                        <TouchableOpacity onPress={() => setSearchQuery('')}>
                            <X size={16} color="#94a3b8" />
                        </TouchableOpacity>
                    )}
                </View>
                <ScrollView
                    horizontal
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={styles.filterStrip}
                >
                    <Filter size={16} color="#64748b" style={{ marginRight: 8 }} />
                    {['All', ...STATUS_OPTIONS].map((status) => (
                        <TouchableOpacity
                            key={status}
                            style={[
                                styles.quickFilter,
                                statusFilter === status && { backgroundColor: getStatusColor(status) }
                            ]}
                            onPress={() => setStatusFilter(status)}
                        >
                            <Text style={[styles.quickFilterText, statusFilter === status && { color: '#fff' }]}>
                                {status}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </ScrollView>
            </View>

            <FlatList
                data={filteredLeads}
                renderItem={renderLead}
                keyExtractor={(item: any) => (item && item.id ? item.id.toString() : Math.random().toString())}
                contentContainerStyle={styles.list}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                ListEmptyComponent={
                    <View style={styles.empty}>
                        <User size={48} color="#e2e8f0" />
                        <Text style={styles.emptyText}>No matching leads found.</Text>
                    </View>
                }
            />

            {/* FAB - Add Lead */}
            <TouchableOpacity
                style={styles.fab}
                onPress={() => setAddModalVisible(true)}
            >
                <Plus color="#fff" size={28} />
            </TouchableOpacity>

            {/* Add Lead Modal */}
            <Modal
                visible={addModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setAddModalVisible(false)}
            >
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                    style={styles.modalOverlay}
                >
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Add New Lead</Text>
                            <TouchableOpacity onPress={() => setAddModalVisible(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>

                        <ScrollView style={styles.modalForm}>
                            <Text style={styles.label}>Name *</Text>
                            <TextInput
                                style={styles.input}
                                placeholder="Prospect Name"
                                value={newName}
                                onChangeText={setNewName}
                            />

                            <Text style={styles.label}>Mobile *</Text>
                            <TextInput
                                style={styles.input}
                                placeholder="10-digit Indian Mobile"
                                value={newMobile}
                                onChangeText={(val) => {
                                    let cleaned = val.replace(/[^0-9]/g, '');
                                    if (cleaned.length > 10) cleaned = cleaned.slice(-10);
                                    setNewMobile(cleaned);
                                }}
                                keyboardType="phone-pad"
                                maxLength={10}
                            />

                            <Text style={styles.label}>Source</Text>
                            <View style={styles.pickerContainer}>
                                {Array.isArray(sources) && sources.map((s: any) => {
                                    if (!s || !s.id) return null;
                                    const sId = s.id.toString();
                                    return (
                                        <TouchableOpacity
                                            key={sId}
                                            style={[styles.pickerItem, newSource === sId && styles.pickerItemActive]}
                                            onPress={() => setNewSource(sId)}
                                        >
                                            <Text style={[styles.pickerText, newSource === sId && styles.pickerTextActive]}>
                                                {s.source_name}
                                            </Text>
                                        </TouchableOpacity>
                                    );
                                })}
                            </View>

                            <Text style={styles.label}>Remarks</Text>
                            <TextInput
                                style={[styles.input, styles.textArea]}
                                placeholder="Initial interaction details..."
                                value={newRemarks}
                                onChangeText={setNewRemarks}
                                multiline
                                numberOfLines={3}
                            />

                            <TouchableOpacity
                                style={[styles.submitButton, submitting && styles.buttonDisabled]}
                                onPress={handleAddLead}
                                disabled={submitting}
                            >
                                <Text style={styles.submitButtonText}>
                                    {submitting ? 'Creating...' : 'Create Lead'}
                                </Text>
                            </TouchableOpacity>
                        </ScrollView>
                    </View>
                </KeyboardAvoidingView>
            </Modal>

            {/* Update Lead Modal */}
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
                                <Text style={styles.modalTitle}>Lead Details</Text>
                                <Text style={styles.modalSubtitle}>{selectedLead?.name}</Text>
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
                                    <Text style={styles.label}>New Status</Text>
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

                                    <Text style={styles.label}>New Remark *</Text>
                                    <TextInput
                                        style={[styles.input, styles.textArea]}
                                        placeholder="Enter latest interaction..."
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
                                        onPress={handleUpdateLead}
                                        disabled={submitting}
                                    >
                                        <CheckCircle2 color="#fff" size={20} />
                                        <Text style={styles.submitButtonText}>
                                            {submitting ? 'Updating...' : 'Save Interaction'}
                                        </Text>
                                    </TouchableOpacity>

                                    <TouchableOpacity
                                         style={styles.deleteButtonInModal}
                                         onPress={handleDeleteLead}
                                     >
                                         <Trash2 color="#ef4444" size={18} />
                                         <Text style={styles.deleteButtonTextInModal}>Delete Lead</Text>
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
                                                {Array.isArray(history) && history.map((h, index) => (
                                                    <View key={h.id} style={styles.timelineItem}>
                                                        <View style={styles.timelineMarker}>
                                                            <View style={styles.timelineDot} />
                                                            {index !== history.length - 1 && <View style={styles.timelineLine} />}
                                                        </View>
                                                        <View style={styles.timelineContent}>
                                                            <View style={styles.timelineHeader}>
                                                                <Text style={styles.timelineDate}>{formatDateTime(h.created_at)}</Text>
                                                            </View>
                                                            <Text style={styles.timelineRemark}>{h.remark}</Text>
                                                        </View>
                                                    </View>
                                                ))}
                                            </View>
                                        ) : (
                                            <Text style={styles.noHistory}>No follow-up records.</Text>
                                        )}

                                        <View style={[styles.sectionHeader, { marginTop: 24 }]}>
                                            <Play size={16} color="#6366f1" />
                                            <Text style={styles.sectionTitle}>Call Recordings</Text>
                                        </View>

                                        {!loadingHistory && recordings.length > 0 ? (
                                            <View style={{ gap: 10, marginTop: 10 }}>
                                                {recordings.map((rec) => (
                                                    <View key={rec.id} style={styles.recItem}>
                                                        <View style={{ flex: 1 }}>
                                                            <Text style={styles.recTime}>{formatDateTime(rec.call_time)}</Text>
                                                            <Text style={styles.recType}>{rec.type} • {rec.duration}s</Text>
                                                        </View>
                                                        <TouchableOpacity 
                                                            style={[styles.recPlayBtn, playingId === rec.id && { backgroundColor: '#6366f1' }]} 
                                                            onPress={() => handleToggleAudio(rec)}
                                                        >
                                                            {playingId === rec.id && playbackStatus?.isPlaying ? (
                                                                <Pause size={14} color="#fff" />
                                                            ) : (
                                                                <Play size={14} color={playingId === rec.id ? "#fff" : "#6366f1"} />
                                                            )}
                                                        </TouchableOpacity>
                                                    </View>
                                                ))}
                                            </View>
                                        ) : !loadingHistory ? (
                                            <Text style={styles.noHistory}>No recordings found.</Text>
                                        ) : null}
                                    </View>
                                </View>
                            )}
                        </ScrollView>
                    </View>
                </KeyboardAvoidingView>
            </Modal>

            {/* Fresh Modal (Long Press) */}
            <Modal
                visible={actionModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setActionModalVisible(false)}
            >
                <TouchableOpacity
                    style={styles.modalOverlay}
                    activeOpacity={1}
                    onPress={() => setActionModalVisible(false)}
                >
                    <TouchableWithoutFeedback>
                        <View style={[styles.freshModal, { paddingBottom: Math.max(insets.bottom, 24) }]}>
                            <View style={styles.freshModalHeader}>
                                <View style={[styles.dragHandle, { backgroundColor: '#e2e8f0', width: 40, height: 4, borderRadius: 2, marginBottom: 16 }]} />
                                <Text style={styles.freshModalTitle}>{selectedLeadForAction?.name || 'Lead Actions'}</Text>
                                <Text style={styles.freshModalSubtitle}>{selectedLeadForAction?.mobile}</Text>
                                <View style={styles.adminTag}>
                                    <Text style={styles.adminTagText}>ROLE: {userRole.toUpperCase() || 'UNSET'}</Text>
                                </View>
                            </View>

                            <TouchableOpacity 
                                style={styles.freshModalItem} 
                                onPress={() => { setActionModalVisible(false); openUpdateModal(selectedLeadForAction); }}
                            >
                                <View style={styles.freshIcon}>
                                    <History size={20} color="#6366f1" />
                                </View>
                                <Text style={styles.freshModalItemText}>View Details & History</Text>
                            </TouchableOpacity>

                            <TouchableOpacity 
                                style={styles.freshModalItem} 
                                onPress={() => { setActionModalVisible(false); setAssignModalVisible(true); }}
                            >
                                <View style={styles.freshIcon}>
                                    <UserPlus size={20} color="#6366f1" />
                                </View>
                                <Text style={styles.freshModalItemText}>Assign to Executive</Text>
                            </TouchableOpacity>

                            <TouchableOpacity 
                                style={styles.freshModalItem} 
                                onPress={handleDeleteLead}
                            >
                                <View style={[styles.freshIcon, { backgroundColor: '#fee2e2' }]}>
                                    <Trash2 size={20} color="#ef4444" />
                                </View>
                                <Text style={[styles.freshModalItemText, { color: '#ef4444' }]}>Delete Lead</Text>
                            </TouchableOpacity>

                            <TouchableOpacity 
                                style={styles.freshModalCancel} 
                                onPress={() => setActionModalVisible(false)}
                            >
                                <Text style={styles.freshModalCancelText}>Cancel</Text>
                            </TouchableOpacity>
                        </View>
                    </TouchableWithoutFeedback>
                </TouchableOpacity>
            </Modal>

            {/* Assign Modal */}
            <Modal
                visible={assignModalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setAssignModalVisible(false)}
            >
                <View style={styles.modalOverlay}>
                    <View style={[styles.modalContent, { maxHeight: '60%' }]}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Assign Lead</Text>
                            <TouchableOpacity onPress={() => setAssignModalVisible(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>
                        <FlatList
                            data={executives}
                            keyExtractor={(item) => item?.id?.toString() || Math.random().toString()}
                            renderItem={({ item }) => (
                                <TouchableOpacity
                                    style={styles.execItem}
                                    onPress={() => handleAssignLead(item.id)}
                                >
                                    <View style={styles.execAvatar}>
                                        <Text style={styles.execAvatarText}>{(item.name || 'E').charAt(0).toUpperCase()}</Text>
                                    </View>
                                    <Text style={styles.execName}>{item.name}</Text>
                                    <ChevronRight size={18} color="#94a3b8" />
                                </TouchableOpacity>
                            )}
                            ListEmptyComponent={
                                <View style={styles.empty}>
                                    <Text style={styles.emptyText}>No executives found.</Text>
                                </View>
                            }
                        />
                    </View>
                </View>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: {
        flex: 1,
        backgroundColor: '#fff',
    },
    center: {
        flex: 1,
        justifyContent: 'center',
        alignItems: 'center',
    },
    topTools: {
        padding: 12,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    searchBar: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f1f5f9',
        borderRadius: 10,
        paddingHorizontal: 12,
        height: 44,
    },
    searchInput: {
        flex: 1,
        marginLeft: 8,
        fontSize: 14,
        color: '#1e293b',
    },
    filterStrip: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 12,
        paddingLeft: 2,
    },
    quickFilter: {
        paddingHorizontal: 12,
        paddingVertical: 6,
        borderRadius: 20,
        backgroundColor: '#f1f5f9',
        marginRight: 8,
    },
    quickFilterText: {
        fontSize: 12,
        fontWeight: '600',
        color: '#64748b',
    },
    list: {
        padding: 12,
    },
    card: {
        backgroundColor: '#fff',
        borderRadius: 12,
        padding: 12,
        marginBottom: 8,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 1 },
        shadowOpacity: 0.03,
        shadowRadius: 2,
        elevation: 1,
    },
    cardContent: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    avatarCompact: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: '#eff6ff',
        justifyContent: 'center',
        alignItems: 'center',
    },
    avatarTextCompact: {
        color: '#6366f1',
        fontWeight: '700',
        fontSize: 15,
    },
    cardInfo: {
        flex: 1,
        marginLeft: 12,
    },
    leadName: {
        fontSize: 15,
        fontWeight: '700',
        color: '#1e293b',
    },
    mobileTextCompact: {
        fontSize: 12,
        color: '#64748b',
        marginTop: 1,
    },
    itemRight: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    statusBadgeCompact: {
        paddingHorizontal: 8,
        paddingVertical: 3,
        borderRadius: 4,
    },
    statusTextCompact: {
        fontSize: 9,
        fontWeight: '900',
        color: '#fff',
        textTransform: 'uppercase',
    },
    callCircleCompact: {
        width: 32,
        height: 32,
        borderRadius: 16,
        backgroundColor: '#6366f1',
        justifyContent: 'center',
        alignItems: 'center',
    },
    whatsappCircleCompact: {
        width: 32,
        height: 32,
        borderRadius: 16,
        backgroundColor: '#10b981',
        justifyContent: 'center',
        alignItems: 'center',
    },
    actionRowLead: {
        flexDirection: 'row',
        gap: 8,
        marginTop: 6,
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
    fab: {
        position: 'absolute',
        bottom: 24,
        right: 24,
        width: 56,
        height: 56,
        borderRadius: 28,
        backgroundColor: '#6366f1',
        justifyContent: 'center',
        alignItems: 'center',
        shadowColor: '#6366f1',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.3,
        shadowRadius: 6,
        elevation: 5,
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
    pickerContainer: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: 8,
    },
    pickerItem: {
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 8,
        backgroundColor: '#f1f5f9',
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    pickerItemActive: {
        backgroundColor: '#6366f1',
        borderColor: '#6366f1',
    },
    pickerText: {
        fontSize: 13,
        color: '#64748b',
        fontWeight: '600',
    },
    pickerTextActive: {
        color: '#fff',
    },
    statusGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: 8,
        marginBottom: 16,
    },
    freshModal: {
        backgroundColor: '#fff',
        width: '100%',
        borderTopLeftRadius: 32,
        borderTopRightRadius: 32,
        padding: 24,
        paddingBottom: 40,
        elevation: 20,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: -4 },
        shadowOpacity: 0.1,
        shadowRadius: 10,
    },
    dragHandle: {
        width: 40,
        height: 4,
        backgroundColor: '#e2e8f0',
        borderRadius: 2,
        marginBottom: 16,
        alignSelf: 'center',
    },
    freshModalHeader: {
        alignItems: 'center',
        marginBottom: 20,
        paddingBottom: 16,
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    freshModalTitle: {
        fontSize: 18,
        fontWeight: '900',
        color: '#0f172a',
    },
    freshModalSubtitle: {
        fontSize: 14,
        color: '#64748b',
        marginTop: 4,
        fontWeight: '600',
    },
    freshModalItem: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingVertical: 12,
        gap: 12,
    },
    freshIcon: {
        width: 36,
        height: 36,
        borderRadius: 10,
        backgroundColor: '#f5f3ff',
        justifyContent: 'center',
        alignItems: 'center',
    },
    freshModalItemText: {
        fontSize: 15,
        fontWeight: '700',
        color: '#334155',
    },
    freshModalCancel: {
        marginTop: 16,
        paddingVertical: 14,
        borderRadius: 12,
        backgroundColor: '#f8fafc',
        alignItems: 'center',
    },
    freshModalCancelText: {
        fontSize: 15,
        fontWeight: '800',
        color: '#64748b',
    },
    adminTag: {
        marginTop: 8,
        backgroundColor: '#eff6ff',
        paddingHorizontal: 8,
        paddingVertical: 3,
        borderRadius: 4,
    },
    adminTagText: {
        fontSize: 9,
        fontWeight: '900',
        color: '#3b82f6',
    },
    execItem: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 16,
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
        gap: 12,
    },
    execAvatar: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: '#f5f3ff',
        justifyContent: 'center',
        alignItems: 'center',
    },
    execAvatarText: {
        color: '#6366f1',
        fontWeight: '700',
        fontSize: 16,
    },
    execName: {
        flex: 1,
        fontSize: 16,
        fontWeight: '600',
        color: '#1e293b',
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
    },
    recItem: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: 12,
        backgroundColor: '#f8fafc',
        borderRadius: 12,
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    recTime: {
        fontSize: 13,
        fontWeight: '700',
        color: '#1e293b',
    },
    recType: {
        fontSize: 11,
        color: '#64748b',
        marginTop: 2,
    },
    recPlayBtn: {
        width: 36,
        height: 36,
        borderRadius: 18,
        backgroundColor: '#eff6ff',
        justifyContent: 'center',
        alignItems: 'center',
    },
    deleteButtonInModal: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: 8,
        paddingVertical: 12,
        marginBottom: 20,
    },
    deleteButtonTextInModal: {
        color: '#ef4444',
        fontSize: 15,
        fontWeight: '700',
    }
});
