import AsyncStorage from '@react-native-async-storage/async-storage';
import NetInfo from '@react-native-community/netinfo';

const CACHE_PREFIX = 'calldesk_cache_';
const CACHE_EXPIRY_MS = 24 * 60 * 60 * 1000; // 24 hours

export const OfflineManager = {
    // Save data to cache
    save: async (key: string, data: any) => {
        try {
            const cacheItem = {
                timestamp: Date.now(),
                data: data
            };
            await AsyncStorage.setItem(`${CACHE_PREFIX}${key}`, JSON.stringify(cacheItem));
        } catch (error) {
            console.error('Cache save error:', error);
        }
    },

    // Load data from cache
    load: async (key: string) => {
        try {
            const json = await AsyncStorage.getItem(`${CACHE_PREFIX}${key}`);
            if (!json) return null;

            const cacheItem = JSON.parse(json);

            // Check expiry (optional, currently disabled to always show something offline)
            // if (Date.now() - cacheItem.timestamp > CACHE_EXPIRY_MS) return null;

            return cacheItem.data;
        } catch (error) {
            console.error('Cache load error:', error);
            return null;
        }
    },

    // Clear specific cache
    clear: async (key: string) => {
        try {
            await AsyncStorage.removeItem(`${CACHE_PREFIX}${key}`);
        } catch (error) {
            console.error('Cache clear error:', error);
        }
    },

    // Check online status
    isOnline: async () => {
        const state = await NetInfo.fetch();
        return state.isConnected && state.isInternetReachable;
    }
};
