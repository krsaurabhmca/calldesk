import React, { createContext, useContext, useState, useCallback, useRef } from 'react';
import { View, Text, StyleSheet, Animated, TouchableOpacity, Dimensions } from 'react-native';
import { X, CheckCircle2, AlertCircle, Info } from 'lucide-react-native';

type SnackbarType = 'success' | 'error' | 'info';

interface SnackbarContextType {
    showSnackbar: (message: string, type?: SnackbarType) => void;
}

const SnackbarContext = createContext<SnackbarContextType | undefined>(undefined);

export const SnackbarProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
    const [visible, setVisible] = useState(false);
    const [message, setMessage] = useState('');
    const [type, setType] = useState<SnackbarType>('info');
    const fadeAnim = useRef(new Animated.Value(0)).current;
    const translateY = useRef(new Animated.Value(100)).current;

    const hideTimeoutRef = useRef<any>(null);

    const hide = useCallback(() => {
        if (hideTimeoutRef.current) {
            clearTimeout(hideTimeoutRef.current);
            hideTimeoutRef.current = null;
        }

        Animated.parallel([
            Animated.timing(fadeAnim, {
                toValue: 0,
                duration: 300,
                useNativeDriver: true,
            }),
            Animated.timing(translateY, {
                toValue: -20,
                duration: 300,
                useNativeDriver: true,
            }),
        ]).start(() => {
            setVisible(false);
        });
    }, [fadeAnim, translateY]);

    const showSnackbar = useCallback((msg: string, snackType: SnackbarType = 'info') => {
        // Clear existing timeout if any
        if (hideTimeoutRef.current) {
            clearTimeout(hideTimeoutRef.current);
        }

        setMessage(msg);
        setType(snackType);
        setVisible(true);

        // Reset positions for new animation
        fadeAnim.setValue(0);
        translateY.setValue(-50);

        Animated.parallel([
            Animated.timing(fadeAnim, {
                toValue: 1,
                duration: 400,
                useNativeDriver: true,
            }),
            Animated.spring(translateY, {
                toValue: 0,
                tension: 40,
                friction: 7,
                useNativeDriver: true,
            }),
        ]).start();

        // Auto hide after 3 seconds
        hideTimeoutRef.current = setTimeout(() => {
            hide();
        }, 3000);
    }, [fadeAnim, translateY, hide]);

    const getIcon = () => {
        switch (type) {
            case 'success': return <CheckCircle2 size={18} color="#fff" />;
            case 'error': return <AlertCircle size={18} color="#fff" />;
            default: return <Info size={18} color="#fff" />;
        }
    };

    const getBackgroundColor = () => {
        switch (type) {
            case 'success': return '#10b981';
            case 'error': return '#ef4444';
            default: return '#1e293b';
        }
    };

    return (
        <SnackbarContext.Provider value={{ showSnackbar }}>
            {children}
            {visible && (
                <Animated.View
                    style={[
                        styles.container,
                        {
                            opacity: fadeAnim,
                            transform: [{ translateY }],
                            backgroundColor: getBackgroundColor(),
                        },
                    ]}
                >
                    <View style={styles.content}>
                        {getIcon()}
                        <Text style={styles.message}>{message}</Text>
                        <TouchableOpacity onPress={hide} style={styles.closeBtn}>
                            <X size={16} color="#fff" style={{ opacity: 0.8 }} />
                        </TouchableOpacity>
                    </View>
                </Animated.View>
            )}
        </SnackbarContext.Provider>
    );
};

export const useSnackbar = () => {
    const context = useContext(SnackbarContext);
    if (!context) {
        throw new Error('useSnackbar must be used within a SnackbarProvider');
    }
    return context;
};

const styles = StyleSheet.create({
    container: {
        position: 'absolute',
        top: 50,
        left: 20,
        right: 20,
        paddingHorizontal: 16,
        paddingVertical: 14,
        borderRadius: 12,
        flexDirection: 'row',
        alignItems: 'center',
        shadowColor: '#000',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.15,
        shadowRadius: 10,
        elevation: 10,
        zIndex: 9999,
    },
    content: {
        flex: 1,
        flexDirection: 'row',
        alignItems: 'center',
    },
    message: {
        color: '#fff',
        fontSize: 14,
        fontWeight: '600',
        marginLeft: 10,
        flex: 1,
    },
    closeBtn: {
        marginLeft: 10,
    },
});
