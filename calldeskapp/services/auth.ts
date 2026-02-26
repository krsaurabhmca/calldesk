import * as SecureStore from 'expo-secure-store';
import { TOKEN_KEY, USER_KEY } from '../constants/Config';
import { apiCall } from './api';

export const login = async (mobile: string, password: string) => {
    const result = await apiCall('login.php', 'POST', { mobile, password });
    if (result.success) {
        await SecureStore.setItemAsync(TOKEN_KEY, result.data.token);
        await SecureStore.setItemAsync(USER_KEY, JSON.stringify(result.data.user));
    }
    return result;
};

export const signup = async (name: string, mobile: string, password: string) => {
    const result = await apiCall('signup.php', 'POST', { name, mobile, password });
    if (result.success) {
        await SecureStore.setItemAsync(TOKEN_KEY, result.data.token);
        await SecureStore.setItemAsync(USER_KEY, JSON.stringify(result.data.user));
    }
    return result;
};

export const registerOrganization = async (orgName: string, adminName: string, mobile: string, password: string) => {
    const result = await apiCall('register_org.php', 'POST', {
        org_name: orgName,
        name: adminName,
        mobile,
        password
    });
    if (result.success) {
        await SecureStore.setItemAsync(TOKEN_KEY, result.data.token);
        await SecureStore.setItemAsync(USER_KEY, JSON.stringify(result.data.user));
    }
    return result;
};

export const logout = async () => {
    await SecureStore.deleteItemAsync(TOKEN_KEY);
    await SecureStore.deleteItemAsync(USER_KEY);
};

export const getUser = async () => {
    const user = await SecureStore.getItemAsync(USER_KEY);
    return user ? JSON.parse(user) : null;
};

export const isAuthenticated = async () => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    return !!token;
};
