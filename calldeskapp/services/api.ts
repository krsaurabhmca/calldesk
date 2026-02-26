import { BASE_URL, TOKEN_KEY } from '../constants/Config';
import * as SecureStore from 'expo-secure-store';

const getHeaders = async () => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    const headers: any = {
        'Content-Type': 'application/x-www-form-urlencoded', // PHP standard
    };
    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }
    return headers;
};

export const apiCall = async (endpoint: string, method: string = 'GET', body: any = null) => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    const headers = await getHeaders();

    // Append token to URL as ultimate fallback for servers that strip headers
    const separator = endpoint.includes('?') ? '&' : '?';
    const url = `${BASE_URL}/${endpoint}${token ? separator + 'token=' + token : ''}`;

    console.log(`Calling API: ${url} [${method}]`);

    const config: any = {
        method,
        headers,
    };

    if (body || method === 'POST') {
        const formData = new URLSearchParams();
        const token = await SecureStore.getItemAsync(TOKEN_KEY);

        // Add token to body as fallback
        if (token) {
            formData.append('token', token);
        }

        if (body) {
            for (const key in body) {
                if (body[key] !== null && body[key] !== undefined) {
                    formData.append(key, body[key].toString());
                }
            }
        }
        config.body = formData.toString();
    }

    try {
        const response = await fetch(url, config);
        const text = await response.text();

        console.log(`API Response from ${endpoint}:`, text);

        try {
            return JSON.parse(text);
        } catch (e) {
            console.error(`JSON Parse Error for ${url}. Server returned:`, text);
            return { success: false, message: 'Server error: Invalid JSON response' };
        }
    } catch (error) {
        console.error('API Error:', error);
        return { success: false, message: 'Network connection failed' };
    }
};

// JSON Body version for sync_calls
export const apiCallJson = async (endpoint: string, body: any) => {
    const token = await SecureStore.getItemAsync(TOKEN_KEY);
    const separator = endpoint.includes('?') ? '&' : '?';
    const url = `${BASE_URL}/${endpoint}${token ? separator + 'token=' + token : ''}`;

    try {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
            },
            body: JSON.stringify(body),
        });
        const text = await response.text();
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON Parse Error in apiCallJson. Server returned:', text);
            return { success: false, message: 'Server error: Invalid JSON response' };
        }
    } catch (error) {
        return { success: false, message: 'Network connection failed' };
    }
};
