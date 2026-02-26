import React, { useState, useEffect, useCallback } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, Modal, TextInput, ActivityIndicator, Alert, KeyboardAvoidingView, Platform, ScrollView } from 'react-native';
import { MessageSquare, Plus, X, Trash2, CheckCircle2, Circle } from 'lucide-react-native';
import { apiCall } from '../../services/api';
import { useSnackbar } from '../../context/SnackbarContext';

export default function MessagesScreen() {
    const { showSnackbar } = useSnackbar();
    const [messages, setMessages] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [modalVisible, setModalVisible] = useState(false);

    // New Message State
    const [title, setTitle] = useState('');
    const [message, setMessage] = useState('');
    const [isDefault, setIsDefault] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const fetchMessages = async () => {
        setLoading(true);
        const result = await apiCall('whatsapp_messages.php');
        if (result.success) {
            setMessages(result.data);
        }
        setLoading(false);
    };

    useEffect(() => {
        fetchMessages();
    }, []);

    const handleSaveMessage = async () => {
        if (!title || !message) {
            showSnackbar('Title and Message are required', 'error');
            return;
        }

        setSubmitting(true);
        const result = await apiCall('whatsapp_messages.php', 'POST', {
            action: 'add',
            title,
            message,
            is_default: isDefault ? 1 : 0
        });
        setSubmitting(false);

        if (result.success) {
            showSnackbar('Message saved successfully', 'success');
            setModalVisible(false);
            setTitle('');
            setMessage('');
            setIsDefault(false);
            fetchMessages();
        } else {
            showSnackbar(result.message || 'Failed to save message', 'error');
        }
    };

    const handleSetDefault = async (id: number) => {
        const result = await apiCall('whatsapp_messages.php', 'POST', {
            action: 'set_default',
            id
        });
        if (result.success) {
            showSnackbar('Default message updated', 'success');
            fetchMessages();
        }
    };

    const handleDelete = async (id: number) => {
        Alert.alert('Delete Message', 'Are you sure you want to delete this message?', [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Delete',
                style: 'destructive',
                onPress: async () => {
                    const result = await apiCall('whatsapp_messages.php', 'POST', {
                        action: 'delete',
                        id
                    });
                    if (result.success) {
                        showSnackbar('Message deleted', 'success');
                        fetchMessages();
                    }
                }
            }
        ]);
    };

    const renderMessage = ({ item }: any) => (
        <View style={[styles.card, item.is_default == 1 && styles.cardDefault]}>
            <View style={styles.cardHeader}>
                <View style={styles.titleRow}>
                    <Text style={styles.msgTitle}>{item.title}</Text>
                    {item.is_default == 1 && (
                        <View style={styles.defaultBadge}>
                            <Text style={styles.defaultBadgeText}>DEFAULT</Text>
                        </View>
                    )}
                </View>
                <TouchableOpacity onPress={() => handleDelete(item.id)}>
                    <Trash2 size={18} color="#ef4444" />
                </TouchableOpacity>
            </View>
            <Text style={styles.msgText}>{item.message}</Text>
            <TouchableOpacity
                style={styles.setDefaultBtn}
                onPress={() => handleSetDefault(item.id)}
                disabled={item.is_default == 1}
            >
                {item.is_default == 1 ? (
                    <CheckCircle2 size={18} color="#10b981" />
                ) : (
                    <Circle size={18} color="#94a3b8" />
                )}
                <Text style={[styles.setDefaultText, item.is_default == 1 && { color: '#10b981' }]}>
                    {item.is_default == 1 ? 'Default Message' : 'Set as Default'}
                </Text>
            </TouchableOpacity>
        </View>
    );

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.headerTitle}>Saved Messages</Text>
                <TouchableOpacity style={styles.addBtn} onPress={() => setModalVisible(true)}>
                    <Plus size={20} color="#fff" />
                    <Text style={styles.addBtnText}>New Message</Text>
                </TouchableOpacity>
            </View>

            {loading ? (
                <ActivityIndicator size="large" color="#6366f1" style={{ marginTop: 40 }} />
            ) : (
                <FlatList
                    data={messages}
                    renderItem={renderMessage}
                    keyExtractor={(item) => item.id.toString()}
                    contentContainerStyle={styles.list}
                    ListEmptyComponent={
                        <View style={styles.empty}>
                            <MessageSquare size={48} color="#e2e8f0" />
                            <Text style={styles.emptyText}>No saved messages. Add one to get started!</Text>
                        </View>
                    }
                />
            )}

            <Modal
                visible={modalVisible}
                animationType="slide"
                transparent={true}
                onRequestClose={() => setModalVisible(false)}
            >
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                    style={styles.modalOverlay}
                >
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>Add New Template</Text>
                            <TouchableOpacity onPress={() => setModalVisible(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>

                        <ScrollView style={styles.modalBody}>
                            <Text style={styles.label}>Template Title</Text>
                            <TextInput
                                style={styles.input}
                                value={title}
                                onChangeText={setTitle}
                                placeholder="e.g., Welcome Message"
                            />

                            <Text style={styles.label}>Message Content</Text>
                            <TextInput
                                style={[styles.input, styles.textArea]}
                                value={message}
                                onChangeText={setMessage}
                                placeholder="Your WhatsApp message..."
                                multiline
                                numberOfLines={4}
                            />

                            <TouchableOpacity
                                style={styles.checkboxContainer}
                                onPress={() => setIsDefault(!isDefault)}
                            >
                                {isDefault ? (
                                    <CheckCircle2 size={20} color="#6366f1" />
                                ) : (
                                    <Circle size={20} color="#94a3b8" />
                                )}
                                <Text style={styles.checkboxLabel}>Set as Default Template</Text>
                            </TouchableOpacity>

                            <TouchableOpacity
                                style={[styles.submitBtn, submitting && { opacity: 0.7 }]}
                                onPress={handleSaveMessage}
                                disabled={submitting}
                            >
                                {submitting ? (
                                    <ActivityIndicator color="#fff" />
                                ) : (
                                    <Text style={styles.submitBtnText}>Save Template</Text>
                                )}
                            </TouchableOpacity>
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
        backgroundColor: '#f8fafc',
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        padding: 20,
        backgroundColor: '#fff',
        borderBottomWidth: 1,
        borderBottomColor: '#f1f5f9',
    },
    headerTitle: {
        fontSize: 18,
        fontWeight: '800',
        color: '#0f172a',
    },
    addBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: '#6366f1',
        paddingHorizontal: 12,
        paddingVertical: 8,
        borderRadius: 8,
        gap: 6,
    },
    addBtnText: {
        color: '#fff',
        fontWeight: '700',
        fontSize: 13,
    },
    list: {
        padding: 16,
    },
    card: {
        backgroundColor: '#fff',
        borderRadius: 16,
        padding: 16,
        marginBottom: 16,
        borderWidth: 1,
        borderColor: '#f1f5f9',
        elevation: 2,
    },
    cardDefault: {
        borderColor: '#6366f1',
        backgroundColor: '#f5f7ff',
    },
    cardHeader: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: 12,
    },
    titleRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
    },
    msgTitle: {
        fontSize: 16,
        fontWeight: '700',
        color: '#1e293b',
    },
    defaultBadge: {
        backgroundColor: '#6366f1',
        paddingHorizontal: 6,
        paddingVertical: 2,
        borderRadius: 4,
    },
    defaultBadgeText: {
        color: '#fff',
        fontSize: 9,
        fontWeight: '800',
    },
    msgText: {
        fontSize: 14,
        color: '#475569',
        lineHeight: 20,
        marginBottom: 16,
    },
    setDefaultBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 8,
        paddingTop: 12,
        borderTopWidth: 1,
        borderTopColor: '#f1f5f9',
    },
    setDefaultText: {
        fontSize: 13,
        fontWeight: '600',
        color: '#64748b',
    },
    empty: {
        alignItems: 'center',
        marginTop: 100,
    },
    emptyText: {
        marginTop: 12,
        color: '#94a3b8',
        fontSize: 15,
        textAlign: 'center',
        paddingHorizontal: 40,
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
        fontWeight: '800',
        color: '#1e293b',
    },
    modalBody: {
        padding: 20,
    },
    label: {
        fontSize: 14,
        fontWeight: '700',
        color: '#475569',
        marginBottom: 8,
    },
    input: {
        backgroundColor: '#f8fafc',
        borderWidth: 1,
        borderColor: '#e2e8f0',
        borderRadius: 12,
        padding: 12,
        fontSize: 15,
        color: '#1e293b',
        marginBottom: 20,
    },
    textArea: {
        height: 120,
        textAlignVertical: 'top',
    },
    checkboxContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 10,
        marginBottom: 24,
    },
    checkboxLabel: {
        fontSize: 14,
        fontWeight: '600',
        color: '#475569',
    },
    submitBtn: {
        backgroundColor: '#6366f1',
        padding: 16,
        borderRadius: 12,
        alignItems: 'center',
    },
    submitBtnText: {
        color: '#fff',
        fontSize: 16,
        fontWeight: '700',
    },
});
