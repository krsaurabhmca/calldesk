import React, { useState, useEffect } from 'react';
import { View, Text, Modal, StyleSheet, TouchableOpacity, ScrollView, TextInput, Linking, ActivityIndicator } from 'react-native';
import { X, MessageSquare, Save, Plus, Trash2, Check, CheckCircle2 } from 'lucide-react-native';
import { apiCall } from '../services/api';
import { useSnackbar } from '../context/SnackbarContext';
import { OfflineManager } from '../services/offline';

export interface WhatsAppTemplate {
    id: number;
    title: string;
    message: string;
    is_default: number;
}

interface Props {
    visible: boolean;
    onClose: () => void;
    mobile: string;
    name: string;
}

export default function WhatsAppModal({ visible, onClose, mobile, name }: Props) {
    const { showSnackbar } = useSnackbar();
    const [templates, setTemplates] = useState<WhatsAppTemplate[]>([]);
    const [loading, setLoading] = useState(true);
    const [creating, setCreating] = useState(false);

    // New template form state
    const [newTitle, setNewTitle] = useState('');
    const [newMessage, setNewMessage] = useState('');
    const [saving, setSaving] = useState(false);

    useEffect(() => {
        if (visible) {
            fetchTemplates();
        }
    }, [visible]);

    const fetchTemplates = async () => {
        setLoading(true);
        const isOnline = await OfflineManager.isOnline();

        if (!isOnline) {
            const cachedTemplates = await OfflineManager.load('whatsapp_templates');
            if (cachedTemplates) setTemplates(cachedTemplates);
            setLoading(false);
            return;
        }

        const result = await apiCall('whatsapp_messages.php');
        if (result.success) {
            setTemplates(result.data);
            OfflineManager.save('whatsapp_templates', result.data);
        }
        setLoading(false);
    };

    const handleSend = (text: string) => {
        if (!mobile) {
            showSnackbar('No mobile number available', 'error');
            return;
        }
        // Replace variables like {name} with actual data
        const processedText = text.replace(/{name}|{Name}/g, name || 'Customer');

        const url = `whatsapp://send?phone=91${mobile}&text=${encodeURIComponent(processedText)}`;
        Linking.openURL(url).catch(() => {
            showSnackbar('WhatsApp not installed or error opening link', 'error');
        });
        onClose();
    };

    const handleCreateTemplate = async () => {
        if (!newTitle || !newMessage) {
            showSnackbar('Title and Message are required', 'error');
            return;
        }

        setSaving(true);
        const result = await apiCall('whatsapp_messages.php', 'POST', {
            action: 'add',
            title: newTitle,
            message: newMessage,
            is_default: 0
        });

        if (result.success) {
            showSnackbar('Template saved successfully', 'success');
            setCreating(false);
            setNewTitle('');
            setNewMessage('');
            fetchTemplates();
        } else {
            showSnackbar('Failed to save template', 'error');
        }
        setSaving(false);
    };

    const handleDelete = async (id: number) => {
        const result = await apiCall('whatsapp_messages.php', 'POST', {
            action: 'delete',
            id: id
        });
        if (result.success) {
            fetchTemplates(); // Refresh list
        }
    };

    const handleSetDefault = async (id: number) => {
        const result = await apiCall('whatsapp_messages.php', 'POST', {
            action: 'set_default',
            id: id
        });
        if (result.success) {
            fetchTemplates();
        }
    };

    return (
        <Modal
            visible={visible}
            animationType="slide"
            transparent={true}
            onRequestClose={onClose}
        >
            <View style={styles.modalOverlay}>
                <View style={styles.modalContent}>
                    <View style={styles.modalHeader}>
                        <View style={{ flexDirection: 'row', alignItems: 'center', gap: 10 }}>
                            <MessageSquare size={20} color="#25D366" />
                            <Text style={styles.modalTitle}>Choose Template</Text>
                        </View>
                        <TouchableOpacity onPress={onClose}>
                            <X size={24} color="#64748b" />
                        </TouchableOpacity>
                    </View>

                    {loading ? (
                        <ActivityIndicator size="large" color="#25D366" style={{ marginTop: 40 }} />
                    ) : (
                        <ScrollView style={styles.listContainer} contentContainerStyle={{ paddingBottom: 20 }}>

                            {/* Create New Toggle */}
                            {!creating && (
                                <TouchableOpacity
                                    style={styles.createNewBtn}
                                    onPress={() => setCreating(true)}
                                >
                                    <Plus size={18} color="#fff" />
                                    <Text style={styles.createNewText}>Create New Template</Text>
                                </TouchableOpacity>
                            )}

                            {/* Create Form */}
                            {creating && (
                                <View style={styles.createForm}>
                                    <Text style={styles.formTitle}>New Template</Text>

                                    <TextInput
                                        style={styles.input}
                                        placeholder="Template Name (e.g. Follow-up 1)"
                                        value={newTitle}
                                        onChangeText={setNewTitle}
                                    />

                                    <TextInput
                                        style={[styles.input, styles.textArea]}
                                        placeholder="Message (Use {name} for variable)"
                                        value={newMessage}
                                        onChangeText={setNewMessage}
                                        multiline
                                        numberOfLines={3}
                                    />

                                    <View style={styles.formActions}>
                                        <TouchableOpacity
                                            style={styles.cancelBtn}
                                            onPress={() => setCreating(false)}
                                        >
                                            <Text style={styles.cancelText}>Cancel</Text>
                                        </TouchableOpacity>

                                        <TouchableOpacity
                                            style={styles.saveBtn}
                                            onPress={handleCreateTemplate}
                                            disabled={saving}
                                        >
                                            {saving ? <ActivityIndicator size="small" color="#fff" /> : (
                                                <>
                                                    <Save size={16} color="#fff" />
                                                    <Text style={styles.saveText}>Save</Text>
                                                </>
                                            )}
                                        </TouchableOpacity>
                                    </View>
                                </View>
                            )}

                            <Text style={styles.sectionHeader}>Quick Templates</Text>

                            {templates.map((tpl) => (
                                <TouchableOpacity
                                    key={tpl.id}
                                    style={[styles.templateCard, tpl.is_default === 1 && styles.defaultCard]}
                                    onPress={() => handleSend(tpl.message)}
                                >
                                    <View style={styles.templateHeader}>
                                        <Text style={styles.templateTitle}>{tpl.title}</Text>
                                        {tpl.is_default === 1 && (
                                            <View style={styles.defaultBadge}>
                                                <CheckCircle2 size={10} color="#fff" />
                                                <Text style={styles.defaultText}>Default</Text>
                                            </View>
                                        )}
                                    </View>

                                    <Text style={styles.templateMsg} numberOfLines={2}>
                                        {tpl.message.replace(/{name}|{Name}/g, name || '...')}
                                    </Text>

                                    <View style={styles.cardActions}>
                                        {/* Set Default */}
                                        {tpl.is_default !== 1 && (
                                            <TouchableOpacity
                                                style={styles.actionIcon}
                                                onPress={(e) => { e.stopPropagation(); handleSetDefault(tpl.id); }}
                                            >
                                                <Check size={14} color="#64748b" />
                                                <Text style={styles.actionText}>Set Default</Text>
                                            </TouchableOpacity>
                                        )}

                                        {/* Delete */}
                                        <TouchableOpacity
                                            style={styles.actionIcon}
                                            onPress={(e) => { e.stopPropagation(); handleDelete(tpl.id); }}
                                        >
                                            <Trash2 size={14} color="#ef4444" />
                                        </TouchableOpacity>
                                    </View>
                                </TouchableOpacity>
                            ))}

                            {templates.length === 0 && !creating && (
                                <Text style={styles.emptyText}>No templates found. Create one to get started!</Text>
                            )}

                        </ScrollView>
                    )}
                </View>
            </View>
        </Modal>
    );
}

const styles = StyleSheet.create({
    modalOverlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: '#fff',
        borderTopLeftRadius: 24,
        borderTopRightRadius: 24,
        maxHeight: '85%',
        height: '70%',
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
        fontWeight: '700',
        color: '#1e293b',
    },
    listContainer: {
        padding: 20,
    },
    createNewBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        backgroundColor: '#25D366',
        padding: 12,
        borderRadius: 12,
        marginBottom: 20,
        gap: 8,
    },
    createNewText: {
        color: '#fff',
        fontWeight: '700',
        fontSize: 14,
    },
    createForm: {
        backgroundColor: '#f8fafc',
        padding: 16,
        borderRadius: 12,
        marginBottom: 20,
        borderWidth: 1,
        borderColor: '#e2e8f0',
    },
    formTitle: {
        fontSize: 15,
        fontWeight: '700',
        marginBottom: 12,
        color: '#334155',
    },
    input: {
        backgroundColor: '#fff',
        borderWidth: 1,
        borderColor: '#cbd5e1',
        borderRadius: 8,
        padding: 10,
        marginBottom: 10,
        fontSize: 14,
    },
    textArea: {
        height: 80,
        textAlignVertical: 'top',
    },
    formActions: {
        flexDirection: 'row',
        justifyContent: 'flex-end',
        gap: 10,
    },
    cancelBtn: {
        paddingHorizontal: 16,
        paddingVertical: 10,
        borderRadius: 8,
        backgroundColor: '#e2e8f0',
    },
    cancelText: {
        color: '#64748b',
        fontWeight: '600',
    },
    saveBtn: {
        paddingHorizontal: 16,
        paddingVertical: 10,
        borderRadius: 8,
        backgroundColor: '#25D366',
        flexDirection: 'row',
        alignItems: 'center',
        gap: 6,
    },
    saveText: {
        color: '#fff',
        fontWeight: '700',
    },
    sectionHeader: {
        fontSize: 13,
        fontWeight: '700',
        color: '#94a3b8',
        textTransform: 'uppercase',
        marginBottom: 12,
    },
    templateCard: {
        backgroundColor: '#fff',
        borderWidth: 1,
        borderColor: '#e2e8f0',
        borderRadius: 12,
        padding: 14,
        marginBottom: 12,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 2 },
        shadowOpacity: 0.05,
        shadowRadius: 3,
        elevation: 1,
    },
    defaultCard: {
        borderColor: '#25D366',
        backgroundColor: '#f0fdf4',
    },
    templateHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 6,
    },
    templateTitle: {
        fontSize: 16,
        fontWeight: '700',
        color: '#1e293b',
    },
    defaultBadge: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#25D366',
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 4,
        gap: 4,
    },
    defaultText: {
        color: '#fff',
        fontSize: 10,
        fontWeight: '700',
    },
    templateMsg: {
        fontSize: 13,
        color: '#475569',
        lineHeight: 18,
        marginBottom: 10,
    },
    cardActions: {
        flexDirection: 'row',
        justifyContent: 'flex-end',
        gap: 12,
        marginTop: 6,
        paddingTop: 8,
        borderTopWidth: 1,
        borderTopColor: 'rgba(0,0,0,0.05)',
    },
    actionIcon: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        padding: 4,
    },
    actionText: {
        fontSize: 12,
        color: '#64748b',
        fontWeight: '600',
    },
    emptyText: {
        textAlign: 'center',
        color: '#94a3b8',
        marginTop: 20,
        fontStyle: 'italic',
    },
});
