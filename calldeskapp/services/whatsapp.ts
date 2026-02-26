import { apiCall } from './api';
import { OfflineManager } from './offline';
import { Linking, Alert } from 'react-native';

export interface WhatsAppTemplate {
    id: number;
    title: string;
    message: string;
    is_default: number;
}

export const getTemplates = async (): Promise<WhatsAppTemplate[]> => {
    const isOnline = await OfflineManager.isOnline();
    if (!isOnline) {
        const cached = await OfflineManager.load('whatsapp_templates');
        return cached || [];
    }

    const result = await apiCall('whatsapp_messages.php');
    if (result.success) {
        OfflineManager.save('whatsapp_templates', result.data);
        return result.data;
    }
    return [];
};

export const sendWhatsAppDirectly = async (mobile: string, name: string): Promise<boolean> => {
    if (!mobile) {
        Alert.alert('Error', 'No mobile number available');
        return false;
    }

    try {
        const templates = await getTemplates();
        const defaultTemplate = templates.find(t => parseInt(t.is_default.toString()) === 1);

        if (defaultTemplate) {
            const text = defaultTemplate.message.replace(/{name}|{Name}/g, name || 'Prospect');
            const url = `whatsapp://send?phone=91${mobile}&text=${encodeURIComponent(text)}`;

            const canOpen = await Linking.canOpenURL(url);
            if (canOpen) {
                await Linking.openURL(url);
                return true;
            } else {
                // Fallback for some android versions or simulators
                await Linking.openURL(url);
                return true;
            }
        }
    } catch (e) {
        console.error("WhatsApp Error", e);
    }

    return false; // No default template found or error
};
