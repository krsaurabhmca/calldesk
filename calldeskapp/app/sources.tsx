import React, { useState, useEffect } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, Modal, TextInput, ActivityIndicator, Alert, RefreshControl } from 'react-native';
import { Plus, Trash2, Search, X, Flag, AlertCircle, RefreshCcw, ShieldCheck } from 'lucide-react-native';
import { apiCall } from '../services/api';
import { getUser } from '../services/auth';
import { useRouter } from 'expo-router';
import { useSnackbar } from '../context/SnackbarContext';

export default function SourceManagement() {
    const { showSnackbar } = useSnackbar();
    const router = useRouter();
    const [sources, setSources] = useState<any[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [showAddModal, setShowAddModal] = useState(false);
    const [newName, setNewName] = useState('');
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [userRole, setUserRole] = useState<string | null>(null);

    const fetchSources = async () => {
        if (!refreshing) setLoading(true);
        setError(null);

        const userData = await getUser();
        setUserRole(userData?.role || 'executive');

        if (userData?.role !== 'admin') {
            setLoading(false);
            setRefreshing(false);
            return;
        }

        const res = await apiCall('sources.php');
        if (res.success) {
            setSources(res.data);
        } else {
            setError(res.message);
        }
        setLoading(false);
        setRefreshing(false);
    };

    useEffect(() => {
        fetchSources();
    }, []);

    const onRefresh = React.useCallback(() => {
        setRefreshing(true);
        fetchSources();
    }, []);

    const handleAdd = async () => {
        if (!newName) return;
        setSaving(true);
        const res = await apiCall('sources.php', 'POST', {
            action: 'add',
            source_name: newName
        });
        if (res.success) {
            showSnackbar('Source added', 'success');
            setNewName('');
            setShowAddModal(false);
            fetchSources();
        } else {
            showSnackbar(res.message, 'error');
        }
        setSaving(false);
    };

    const handleDelete = (id: number, name: string) => {
        Alert.alert('Delete Source', `Delete "${name}"? This might affect leads using this source.`, [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Delete',
                style: 'destructive',
                onPress: async () => {
                    const res = await apiCall('sources.php', 'POST', { action: 'delete', id });
                    if (res.success) {
                        showSnackbar('Source deleted', 'success');
                        fetchSources();
                    }
                }
            }
        ]);
    };

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

    if (error && !refreshing && sources.length === 0) {
        return (
            <View style={styles.center}>
                <AlertCircle size={48} color="#ef4444" style={{ marginBottom: 16 }} />
                <Text style={styles.errorTitle}>Database Issue</Text>
                <Text style={styles.errorSub}>{error}</Text>
                <TouchableOpacity style={styles.retryBtn} onPress={fetchSources}>
                    <RefreshCcw size={18} color="#fff" style={{ marginRight: 8 }} />
                    <Text style={styles.retryText}>Try Again</Text>
                </TouchableOpacity>
            </View>
        );
    }

    return (
        <View style={styles.container}>
            <View style={styles.header}>
                <Text style={styles.title}>Lead Sources</Text>
                <TouchableOpacity style={styles.addBtn} onPress={() => setShowAddModal(true)}>
                    <Plus color="#fff" size={20} />
                </TouchableOpacity>
            </View>

            <FlatList
                data={sources}
                contentContainerStyle={{ padding: 20 }}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
                renderItem={({ item }) => (
                    <View style={styles.row}>
                        <View style={styles.info}>
                            <Flag size={18} color="#94a3b8" />
                            <Text style={styles.name}>{item.source_name}</Text>
                        </View>
                        <TouchableOpacity onPress={() => handleDelete(item.id, item.source_name)}>
                            <Trash2 size={18} color="#ef4444" />
                        </TouchableOpacity>
                    </View>
                )}
                ListEmptyComponent={<Text style={styles.empty}>No sources added yet</Text>}
            />

            <Modal visible={showAddModal} transparent animationType="slide">
                <View style={styles.modalOverlay}>
                    <View style={styles.modalContent}>
                        <View style={styles.modalHeader}>
                            <Text style={styles.modalTitle}>New Lead Source</Text>
                            <TouchableOpacity onPress={() => setShowAddModal(false)}>
                                <X size={24} color="#64748b" />
                            </TouchableOpacity>
                        </View>
                        <TextInput
                            style={styles.input}
                            placeholder="Source Name (e.g., Instagram, Radio)"
                            value={newName}
                            onChangeText={setNewName}
                            autoFocus
                        />
                        <TouchableOpacity style={styles.saveBtn} onPress={handleAdd} disabled={saving}>
                            {saving ? <ActivityIndicator color="#fff" /> : <Text style={styles.saveText}>Add Source</Text>}
                        </TouchableOpacity>
                    </View>
                </View>
            </Modal>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f8fafc' },
    center: { flex: 1, justifyContent: 'center', alignItems: 'center', padding: 24 },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9' },
    title: { fontSize: 20, fontWeight: '800', color: '#1e293b' },
    addBtn: { width: 40, height: 40, borderRadius: 12, backgroundColor: '#6366f1', justifyContent: 'center', alignItems: 'center' },
    row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', backgroundColor: '#fff', padding: 16, borderRadius: 16, marginBottom: 8, borderWidth: 1, borderColor: '#f1f5f9' },
    info: { flexDirection: 'row', alignItems: 'center', gap: 12 },
    name: { fontSize: 16, fontWeight: '700', color: '#334155' },
    empty: { textAlign: 'center', color: '#94a3b8', marginTop: 40 },
    modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
    modalContent: { backgroundColor: '#fff', borderTopLeftRadius: 30, borderTopRightRadius: 30, padding: 24 },
    modalHeader: { flexDirection: 'row', justifyContent: 'space-between', marginBottom: 20 },
    modalTitle: { fontSize: 18, fontWeight: '800', color: '#1e293b' },
    input: { backgroundColor: '#f8fafc', height: 50, borderRadius: 12, paddingHorizontal: 16, fontSize: 16, borderWidth: 1, borderColor: '#e2e8f0', marginBottom: 20 },
    saveBtn: { backgroundColor: '#6366f1', height: 50, borderRadius: 12, justifyContent: 'center', alignItems: 'center' },
    saveText: { color: '#fff', fontSize: 16, fontWeight: '700' },
    errorTitle: { fontSize: 18, fontWeight: '800', color: '#1e293b', marginBottom: 8 },
    errorSub: { fontSize: 14, color: '#64748b', textAlign: 'center', marginBottom: 24 },
    retryBtn: { flexDirection: 'row', backgroundColor: '#6366f1', paddingHorizontal: 20, paddingVertical: 12, borderRadius: 12, alignItems: 'center' },
    retryText: { color: '#fff', fontSize: 16, fontWeight: '700' }
});
