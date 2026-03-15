import { NativeModules, Platform, PermissionsAndroid } from 'react-native';
import * as Linking from 'expo-linking';

const { DirectCallModule } = NativeModules;

export const makeCall = async (phoneNumber: string) => {
    if (Platform.OS === 'android') {
        try {
            const hasPermission = await PermissionsAndroid.check(PermissionsAndroid.PERMISSIONS.CALL_PHONE);
            
            if (!hasPermission) {
                const granted = await PermissionsAndroid.request(
                    PermissionsAndroid.PERMISSIONS.CALL_PHONE,
                    {
                        title: "Phone Call Permission",
                        message: "CallDesk needs access to make calls directly from the app.",
                        buttonNeutral: "Ask Me Later",
                        buttonNegative: "Cancel",
                        buttonPositive: "OK"
                    }
                );
                
                if (granted !== PermissionsAndroid.RESULTS.GRANTED) {
                    // Fallback to standard dialer if permission denied
                    Linking.openURL(`tel:${phoneNumber}`);
                    return;
                }
            }
            
            // Use native module for direct call
            if (DirectCallModule) {
                DirectCallModule.makeCall(phoneNumber);
            } else {
                // Fallback if module failed to load
                Linking.openURL(`tel:${phoneNumber}`);
            }
        } catch (err) {
            console.error(err);
            Linking.openURL(`tel:${phoneNumber}`);
        }
    } else {
        // iOS still requires standard tel: linking
        Linking.openURL(`tel:${phoneNumber}`);
    }
};
