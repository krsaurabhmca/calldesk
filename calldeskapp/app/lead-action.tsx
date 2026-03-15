import React, { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, StyleSheet, ActivityIndicator, TextInput, ScrollView, KeyboardAvoidingView, Platform, SafeAreaView } from 'react-native';
import { useLocalSearchParams, useRouter, Stack } from 'expo-router';
import { apiCall } from '../services/api';
import { ArrowLeft, User, Phone, Tag, StickyNote, Calendar as CalendarIcon, Save, Trash2, UserPlus, CheckCircle2 } from 'lucide-react-native';
import DateTimePicker from '@react-native-community/datetimepicker';
import { useSnackbar } from '../context/SnackbarContext';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

const STATUS_OPTIONS = ['Pending', 'Follow-up', 'Interested', 'Converted', 'Lost'];

export default function LeadActionScreen() {
    const params = useLocalSearchParams();
    const { autoNumber, leadId, leadName, autoAction: initialAction } = params;
    
    const [currentAction, setCurrentAction] = useState(initialAction);
    const [currentLeadId, setCurrentLeadId] = useState(leadId);
    const [currentLeadName, setCurrentLeadName] = useState(leadName);
    const router = useRouter();
    const { showSnackbar } = useSnackbar();
    const insets = useSafeAreaInsets();

    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [sources, setSources] = useState<any[]>([]);
    
    // Form fields
    const [name, setName] = useState('');
    const [mobile, setMobile] = useState('');
    const [status, setStatus] = useState('Interested');
    const [remark, setRemark] = useState('');
    const [sourceId, setSourceId] = useState('0');
    const [nextFollowUp, setNextFollowUp] = useState('');
    const [showDatePicker, setShowDatePicker] = useState(false);

    useEffect(() => {
        const prepare = async () => {
            setLoading(true);
            const phoneNumber = autoNumber as string;
            setMobile(phoneNumber || '');

            // Fetch sources
            const sourceRes = await apiCall('sources.php', 'GET');
            if (sourceRes.success) {
                setSources(sourceRes.data);
                if (sourceRes.data.length > 0) setSourceId(sourceRes.data[0].id.toString());
            }

            // Secondary Check: If we are in 'add' mode, verify if lead already exists
            // This handles cases where deep link check might have been slow or missed data
            if (initialAction === 'add' && phoneNumber) {
                const checkRes = await apiCall(`leads.php?search=${phoneNumber}`, 'GET');
                if (checkRes.success && checkRes.data) {
                    const exactMatch = checkRes.data.find((l: any) => {
                        const dbNum = l.mobile?.replace(/[^0-9]/g, '').slice(-10);
                        const sNum = phoneNumber.replace(/[^0-9]/g, '').slice(-10);
                        return dbNum === sNum;
                    });
                    
                    if (exactMatch) {
                        setCurrentAction('update');
                        setCurrentLeadId(exactMatch.id.toString());
                        setName(exactMatch.name);
                        setCurrentLeadName(exactMatch.name);
                    }
                }
            } else if (initialAction === 'update') {
                setName(leadName as string || '');
            }
            
            setLoading(false);
        };
        prepare();
    }, [autoNumber, initialAction, leadName]);

    const handleSave = async () => {
        if (!name) {
            showSnackbar('Please enter lead name', 'error');
            return;
        }

        setSubmitting(true);
        try {
            if (currentAction === 'add') {
                const result = await apiCall('leads.php', 'POST', {
                    name,
                    mobile,
                    source_id: sourceId,
                    remarks: remark || 'Added from call'
                });
                if (result.success) {
                    showSnackbar('Lead added successfully', 'success');
                    router.replace('/(tabs)/leads');
                } else {
                    showSnackbar(result.message || 'Failed to add lead', 'error');
                }
            } else {
                // Update interaction
                const result = await apiCall('followups.php', 'POST', {
                    lead_id: currentLeadId,
                    status,
                    remark: remark || 'Follow up after call',
                    next_follow_up_date: nextFollowUp,
                    name: name // Updating lead name if changed
                });
                if (result.success) {
                    showSnackbar('Interaction saved successfully', 'success');
                    router.replace('/(tabs)/leads');
                } else {
                    showSnackbar(result.message || 'Failed to save interaction', 'error');
                }
            }
        } catch (e) {
            showSnackbar('Something went wrong', 'error');
        } finally {
            setSubmitting(false);
        }
    };

    const getStatusColor = (s: string) => {
        switch (s) {
            case 'Pending': return '#f59e0b';
            case 'Follow-up': return '#6366f1';
            case 'Interested': return '#10b981';
            case 'Converted': return '#059669';
            case 'Lost': return '#ef4444';
            default: return '#64748b';
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
        <View style={[styles.container, { paddingTop: insets.top, paddingBottom: insets.bottom }]}>
            <Stack.Screen options={{ headerShown: false }} />
            <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
                        <ArrowLeft size={24} color="#1e293b" />
                    </TouchableOpacity>
                    <Text style={styles.headerTitle}>
                        {currentAction === 'add' ? 'Add New Lead' : 'Update Interaction'}
                    </Text>
                </View>

                <ScrollView style={styles.content} showsVerticalScrollIndicator={false}>
                    <View style={styles.card}>
                        <View style={styles.inputGroup}>
                            <Text style={styles.label}>Mobile Number</Text>
                            <View style={[styles.inputContainer, styles.disabledInput]}>
                                <Phone size={18} color="#94a3b8" />
                                <TextInput 
                                    style={styles.input} 
                                    value={mobile} 
                                    editable={false} 
                                    placeholder="Mobile"
                                />
                            </View>
                        </View>

                        <View style={styles.inputGroup}>
                            <Text style={styles.label}>Lead Name *</Text>
                            <View style={styles.inputContainer}>
                                <User size={18} color="#6366f1" />
                                <TextInput 
                                    style={styles.input} 
                                    value={name} 
                                    onChangeText={setName} 
                                    placeholder="Enter full name"
                                    autoFocus
                                />
                            </View>
                        </View>

                        {currentAction === 'add' ? (
                            <View style={styles.inputGroup}>
                                <Text style={styles.label}>Lead Source</Text>
                                <View style={styles.sourceGrid}>
                                    {sources.map(s => (
                                        <TouchableOpacity 
                                            key={s.id} 
                                            style={[styles.sourceBtn, sourceId === s.id.toString() && styles.sourceBtnActive]}
                                            onPress={() => setSourceId(s.id.toString())}
                                        >
                                            <Text style={[styles.sourceBtnText, sourceId === s.id.toString() && styles.sourceBtnTextActive]}>
                                                {s.source_name}
                                            </Text>
                                        </TouchableOpacity>
                                    ))}
                                </View>
                            </View>
                        ) : (
                            <View style={styles.inputGroup}>
                                <Text style={styles.label}>Lead Status</Text>
                                <View style={styles.statusGrid}>
                                    {STATUS_OPTIONS.map(s => (
                                        <TouchableOpacity 
                                            key={s} 
                                            style={[
                                                styles.statusBtn, 
                                                status === s && { backgroundColor: getStatusColor(s), borderColor: getStatusColor(s) }
                                            ]}
                                            onPress={() => setStatus(s)}
                                        >
                                            <Text style={[styles.statusBtnText, status === s && styles.statusBtnTextActive]}>
                                                {s}
                                            </Text>
                                        </TouchableOpacity>
                                    ))}
                                </View>
                            </View>
                        )}

                        <View style={styles.inputGroup}>
                            <Text style={styles.label}>Interaction Remark</Text>
                            <View style={[styles.inputContainer, { alignItems: 'flex-start', paddingTop: 12 }]}>
                                <StickyNote size={18} color="#6366f1" style={{ marginTop: 2 }} />
                                <TextInput 
                                    style={[styles.input, styles.textArea]} 
                                    value={remark} 
                                    onChangeText={setRemark} 
                                    placeholder="Briefly describe the call..."
                                    multiline
                                    numberOfLines={3}
                                />
                            </View>
                        </View>

                        {currentAction === 'update' && (
                            <View style={styles.inputGroup}>
                                <Text style={styles.label}>Next Follow-up Date</Text>
                                <TouchableOpacity style={styles.datePicker} onPress={() => setShowDatePicker(true)}>
                                    <CalendarIcon size={18} color="#6366f1" />
                                    <Text style={styles.dateText}>
                                        {nextFollowUp || 'Select Date (Optional)'}
                                    </Text>
                                </TouchableOpacity>
                                {showDatePicker && (
                                    <DateTimePicker
                                        value={nextFollowUp ? new Date(nextFollowUp) : new Date()}
                                        mode="date"
                                        display="default"
                                        minimumDate={new Date()}
                                        onChange={(event, date) => {
                                            setShowDatePicker(false);
                                            if (date) setNextFollowUp(date.toISOString().split('T')[0]);
                                        }}
                                    />
                                )}
                            </View>
                        )}
                    </View>

                    <TouchableOpacity 
                        style={[styles.saveBtn, submitting && styles.disabledBtn]} 
                        onPress={handleSave}
                        disabled={submitting}
                    >
                        {submitting ? (
                            <ActivityIndicator color="#fff" />
                        ) : (
                            <>
                                <CheckCircle2 size={24} color="#fff" />
                                <Text style={styles.saveBtnText}>
                                    {currentAction === 'add' ? 'Create Lead' : 'Save Interaction'}
                                </Text>
                            </>
                        )}
                    </TouchableOpacity>
                    <View style={{ height: 40 }} />
                </ScrollView>
            </KeyboardAvoidingView>
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
        backgroundColor: '#f8fafc',
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 20,
        paddingBottom: 16,
        paddingTop: 8,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    backBtn: {
        width: 40,
        height: 40,
        borderRadius: 20,
        backgroundColor: '#f1f5f9',
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 16,
    },
    headerTitle: {
        fontSize: 20,
        fontWeight: '800',
        color: '#1e293b',
    },
    content: {
        flex: 1,
        padding: 20,
    },
    card: {
        backgroundColor: '#fff',
        borderRadius: 24,
        padding: 20,
        marginBottom: 24,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 10,
        elevation: 2,
    },
    inputGroup: {
        marginBottom: 20,
    },
    label: {
        fontSize: 13,
        fontWeight: '700',
        color: '#64748b',
        marginBottom: 8,
        marginLeft: 4,
    },
    inputContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f8fafc',
        borderRadius: 16,
        paddingHorizontal: 16,
        borderWidth: 1.5,
        borderColor: '#f1f5f9',
    },
    disabledInput: {
        backgroundColor: '#f1f5f9',
        borderColor: '#f1f5f9',
    },
    input: {
        flex: 1,
        height: 54,
        marginLeft: 12,
        fontSize: 16,
        color: '#1e293b',
        fontWeight: '600',
    },
    textArea: {
        height: 100,
        textAlignVertical: 'top',
        paddingTop: 12,
    },
    statusGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: 8,
    },
    statusBtn: {
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 10,
        borderWidth: 1.5,
        borderColor: '#e2e8f0',
        backgroundColor: '#fff',
    },
    statusBtnText: {
        fontSize: 13,
        fontWeight: '700',
        color: '#64748b',
    },
    statusBtnTextActive: {
        color: '#fff',
    },
    sourceGrid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: 8,
    },
    sourceBtn: {
        paddingHorizontal: 14,
        paddingVertical: 8,
        borderRadius: 10,
        borderWidth: 1.5,
        borderColor: '#e2e8f0',
    },
    sourceBtnActive: {
        borderColor: '#6366f1',
        backgroundColor: '#f5f3ff',
    },
    sourceBtnText: {
        fontSize: 13,
        fontWeight: '700',
        color: '#64748b',
    },
    sourceBtnTextActive: {
        color: '#6366f1',
    },
    datePicker: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#f8fafc',
        borderRadius: 16,
        paddingHorizontal: 16,
        height: 54,
        borderWidth: 1.5,
        borderColor: '#f1f5f9',
    },
    dateText: {
        marginLeft: 12,
        fontSize: 15,
        color: '#334155',
        fontWeight: '600',
    },
    saveBtn: {
        backgroundColor: '#6366f1',
        height: 64,
        borderRadius: 20,
        flexDirection: 'row',
        justifyContent: 'center',
        alignItems: 'center',
        gap: 12,
        shadowColor: '#6366f1',
        shadowOffset: { width: 0, height: 8 },
        shadowOpacity: 0.3,
        shadowRadius: 15,
        elevation: 8,
    },
    saveBtnText: {
        color: '#fff',
        fontSize: 18,
        fontWeight: '800',
    },
    disabledBtn: {
        opacity: 0.7,
    }
});
