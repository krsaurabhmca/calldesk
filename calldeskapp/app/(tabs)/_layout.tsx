import { Tabs } from 'expo-router';
import { LayoutDashboard, Users, CalendarCheck, PhoneCall, LogOut, MessageSquare, UserCog, MoreHorizontal, PhoneCall as PhoneIcon } from 'lucide-react-native';
import { TouchableOpacity, Alert, Platform, View, StyleSheet } from 'react-native';
import { useRouter } from 'expo-router';
import { logout, getUser } from '../../services/auth';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useEffect, useState } from 'react';
import DialerModal from '../../components/DialerModal';

export default function TabLayout() {
    const router = useRouter();
    const insets = useSafeAreaInsets();
    const [user, setUser] = useState<any>(null);
    const [dialerVisible, setDialerVisible] = useState(false);

    useEffect(() => {
        const fetchUser = async () => {
            const u = await getUser();
            setUser(u);
        };
        fetchUser();
    }, []);

    const handleLogout = () => {
        Alert.alert('Logout', 'Are you sure you want to exit?', [
            { text: 'Cancel', style: 'cancel' },
            {
                text: 'Logout',
                style: 'destructive',
                onPress: async () => {
                    await logout();
                    router.replace('/(auth)/login');
                }
            },
        ]);
    };

    return (
        <View style={{ flex: 1 }}>
            <Tabs
                screenOptions={{
                    headerShown: true,

                    // ===== HEADER TEXT =====
                    headerTitleStyle: {
                        fontWeight: '800',
                        fontSize: 18,
                        color: '#0f172a',
                        letterSpacing: -0.3,
                    },

                    // ===== HEADER BOX =====
                    headerStyle: {
                        backgroundColor: '#ffffff',
                        borderBottomWidth: 1,
                        borderBottomColor: '#f1f5f9',
                        elevation: 0,
                        shadowOpacity: 0,
                    },

                    headerTitleAlign: 'left',

                    // remove extra top padding (MAIN FIX)
                    headerTitleContainerStyle: {
                        marginLeft: 12,
                    },

                    headerRightContainerStyle: {
                        marginRight: 10,
                    },

                    // ===== LOGOUT BUTTON =====
                    headerRight: () => (
                        <TouchableOpacity
                            onPress={handleLogout}
                            style={{
                                backgroundColor: '#fee2e2',
                                padding: 8,
                                borderRadius: 10,
                                marginRight: 15,
                            }}
                        >
                            <LogOut color="#ef4444" size={18} />
                        </TouchableOpacity>
                    ),

                    // ===== TAB BAR =====
                    tabBarActiveTintColor: '#6366f1',
                    tabBarInactiveTintColor: '#94a3b8',
                    tabBarStyle: {
                        height: 65 + insets.bottom,
                        paddingBottom: insets.bottom > 0 ? insets.bottom : 10,
                        paddingTop: 10,
                        borderTopWidth: 1,
                        borderTopColor: '#f1f5f9',
                        backgroundColor: '#fff',
                        elevation: 8,
                    },

                    tabBarLabelStyle: {
                        fontSize: 11,
                        fontWeight: '700',
                        marginTop: 2,
                    },
                }}
            >
                <Tabs.Screen
                    name="index"
                    options={{
                        title: 'Dashboard',
                        tabBarIcon: ({ color }) => <LayoutDashboard color={color} size={24} />,
                    }}
                />
                <Tabs.Screen
                    name="leads"
                    options={{
                        title: 'Leads',
                        tabBarIcon: ({ color }) => <Users color={color} size={24} />,
                    }}
                />
                <Tabs.Screen
                    name="tasks"
                    options={{
                        title: 'Tasks',
                        tabBarIcon: ({ color }) => <CalendarCheck color={color} size={24} />,
                    }}
                />
                <Tabs.Screen
                    name="calls"
                    options={{
                        title: 'Calls',
                        tabBarIcon: ({ color }) => <PhoneCall color={color} size={24} />,
                    }}
                />
                <Tabs.Screen
                    name="more"
                    options={{
                        title: 'More',
                        tabBarIcon: ({ color }) => <MoreHorizontal color={color} size={24} />,
                    }}
                />

                {/* Hidden tabs kept for routing purposes only */}
                <Tabs.Screen
                    name="messages"
                    options={{
                        href: null,
                        headerTitle: 'WhatsApp Templates'
                    }}
                />
                <Tabs.Screen
                    name="users"
                    options={{
                        href: null,
                        headerTitle: 'User Management'
                    }}
                />
            </Tabs>

            <TouchableOpacity
                style={[styles.fab, { bottom: 85 + insets.bottom }]}
                onPress={() => setDialerVisible(true)}
                activeOpacity={0.8}
            >
                <PhoneIcon color="#fff" size={28} />
            </TouchableOpacity>

            <DialerModal
                visible={dialerVisible}
                onClose={() => setDialerVisible(false)}
            />
        </View>
    );
}

const styles = StyleSheet.create({
    fab: {
        position: 'absolute',
        right: 20,
        width: 60,
        height: 60,
        borderRadius: 30,
        backgroundColor: '#6366f1',
        justifyContent: 'center',
        alignItems: 'center',
        elevation: 10,
        shadowColor: '#6366f1',
        shadowOffset: { width: 0, height: 4 },
        shadowOpacity: 0.4,
        shadowRadius: 10,
        zIndex: 99,
    }
});
