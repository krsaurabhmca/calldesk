import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Modal, Dimensions, Platform, Linking, TextInput } from 'react-native';
import { Phone, X, Delete, PhoneCall, UserPlus } from 'lucide-react-native';
import { useRouter } from 'expo-router';
import { makeCall } from '../services/dialer';

const screenWidth = Dimensions.get('window').width;

interface DialerModalProps {
    visible: boolean;
    onClose: () => void;
}

export default function DialerModal({ visible, onClose }: DialerModalProps) {
    const [phoneNumber, setPhoneNumber] = useState('');
    const router = useRouter();

    const handleNumberPress = (val: string) => {
        if (phoneNumber.length < 15) {
            setPhoneNumber(prev => prev + val);
        }
    };

    const handleDeletePress = () => {
        setPhoneNumber(prev => prev.slice(0, -1));
    };

    const handleCall = () => {
        if (phoneNumber) {
            makeCall(phoneNumber);
            onClose();
        }
    };

    const handleAddLead = () => {
        onClose();
        router.push({
            pathname: '/leads',
            params: { showAdd: 'true', mobile: phoneNumber }
        });
    };

    const keys = [
        { num: '1', sub: ' ' }, { num: '2', sub: 'ABC' }, { num: '3', sub: 'DEF' },
        { num: '4', sub: 'GHI' }, { num: '5', sub: 'JKL' }, { num: '6', sub: 'MNO' },
        { num: '7', sub: 'PQRS' }, { num: '8', sub: 'TUV' }, { num: '9', sub: 'WXYZ' },
        { num: '*', sub: ' ' }, { num: '0', sub: '+' }, { num: '#', sub: ' ' }
    ];

    return (
        <Modal
            visible={visible}
            animationType="slide"
            transparent={true}
            onRequestClose={onClose}
        >
            <View style={styles.overlay}>
                <View style={styles.modalContent}>
                    <View style={styles.header}>
                        <View style={styles.headerIconBox}>
                            <Phone size={20} color="#6366f1" />
                        </View>
                        <Text style={styles.headerTitle}>Quick Dialer</Text>
                        <TouchableOpacity onPress={onClose} style={styles.closeBtn}>
                            <X size={24} color="#94a3b8" />
                        </TouchableOpacity>
                    </View>

                    <View style={styles.displayArea}>
                        <Text style={styles.displayText} numberOfLines={1}>
                            {phoneNumber || ' '}
                        </Text>
                        {phoneNumber.length > 0 && (
                            <TouchableOpacity onPress={handleDeletePress} style={styles.deleteBtn}>
                                <Delete color="#ef4444" size={24} />
                            </TouchableOpacity>
                        )}
                    </View>

                    <View style={styles.keypad}>
                        {keys.map((key, index) => (
                            <TouchableOpacity
                                key={index}
                                style={styles.key}
                                onPress={() => handleNumberPress(key.num)}
                                onLongPress={() => key.num === '0' && handleNumberPress('+')}
                            >
                                <Text style={styles.keyNum}>{key.num}</Text>
                                <Text style={styles.keySub}>{key.sub}</Text>
                            </TouchableOpacity>
                        ))}
                    </View>

                    <View style={styles.actions}>
                        <TouchableOpacity
                            style={[styles.callBtn, !phoneNumber && styles.disabledBtn]}
                            onPress={handleCall}
                            disabled={!phoneNumber}
                        >
                            <PhoneCall color="#fff" size={32} />
                        </TouchableOpacity>
                    </View>

                    {phoneNumber.length >= 10 && (
                        <TouchableOpacity style={styles.addLeadBtn} onPress={handleAddLead}>
                            <UserPlus size={18} color="#6366f1" />
                            <Text style={styles.addLeadText}>Save as New Lead</Text>
                        </TouchableOpacity>
                    )}
                </View>
            </View>
        </Modal>
    );
}

const styles = StyleSheet.create({
    overlay: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.5)',
        justifyContent: 'flex-end',
    },
    modalContent: {
        backgroundColor: '#fff',
        borderTopLeftRadius: 32,
        borderTopRightRadius: 32,
        padding: 24,
        paddingBottom: Platform.OS === 'ios' ? 50 : 30,
        elevation: 20,
        shadowColor: '#000',
        shadowOffset: { width: 0, height: -10 },
        shadowOpacity: 0.1,
        shadowRadius: 20,
    },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        marginBottom: 30,
    },
    headerIconBox: {
        width: 36,
        height: 36,
        borderRadius: 10,
        backgroundColor: '#f5f3ff',
        justifyContent: 'center',
        alignItems: 'center',
        marginRight: 12,
    },
    headerTitle: {
        fontSize: 18,
        fontWeight: '800',
        color: '#1e293b',
        flex: 1,
    },
    closeBtn: {
        padding: 4,
    },
    displayArea: {
        width: '100%',
        height: 80,
        backgroundColor: '#f8fafc',
        borderRadius: 20,
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: 20,
        marginBottom: 30,
        borderWidth: 1,
        borderColor: '#f1f5f9',
    },
    displayText: {
        flex: 1,
        fontSize: 36,
        fontWeight: '800',
        color: '#0f172a',
        textAlign: 'center',
    },
    deleteBtn: {
        padding: 8,
    },
    keypad: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        justifyContent: 'center',
        gap: 20,
        marginBottom: 30,
    },
    key: {
        width: (screenWidth - 100) / 3,
        height: (screenWidth - 100) / 3,
        borderRadius: (screenWidth - 100) / 6,
        backgroundColor: '#f1f5f9',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 1,
    },
    keyNum: {
        fontSize: 28,
        fontWeight: '700',
        color: '#334155',
    },
    keySub: {
        fontSize: 10,
        color: '#94a3b8',
        fontWeight: '600',
        marginTop: -2,
    },
    actions: {
        alignItems: 'center',
        marginBottom: 20,
    },
    callBtn: {
        width: 80,
        height: 80,
        borderRadius: 40,
        backgroundColor: '#10b981',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 8,
        shadowColor: '#10b981',
        shadowOffset: { width: 0, height: 6 },
        shadowOpacity: 0.3,
        shadowRadius: 12,
    },
    disabledBtn: {
        backgroundColor: '#d1d5db',
        elevation: 0,
        shadowOpacity: 0,
    },
    addLeadBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'center',
        gap: 8,
        backgroundColor: '#f5f3ff',
        paddingVertical: 12,
        borderRadius: 16,
        borderWidth: 1,
        borderColor: '#e0e7ff',
    },
    addLeadText: {
        fontSize: 15,
        fontWeight: '700',
        color: '#6366f1',
    }
});
