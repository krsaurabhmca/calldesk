import React, { useEffect, useRef } from 'react';
import { AppState, AppStateStatus } from 'react-native';
import { Stack, useRouter } from "expo-router";
import * as Linking from 'expo-linking';
import { StatusBar } from "expo-status-bar";
import { SafeAreaProvider } from 'react-native-safe-area-context';
import { SnackbarProvider } from "../context/SnackbarContext";
import { registerBackgroundSync } from "../services/backgroundSync";
import { runAutoSync } from "../services/autoSync";
import { apiCall } from '../services/api';

export default function RootLayout() {
  const appState = useRef<AppStateStatus>(AppState.currentState);
  const router = useRouter();

  const handleDeepLink = async (url: string | null) => {
    if (!url) return;
    console.log('Deep Link received:', url);
    
    const { path, queryParams } = Linking.parse(url);
    // Path might be "" or null if root is used
    if (queryParams?.reason === 'call_ended' && queryParams?.number) {
      let phoneNumber = queryParams.number as string;
      // Clean to 10-digit Indian number
      phoneNumber = phoneNumber.replace(/[^0-9]/g, '');
      if (phoneNumber.length > 10) {
        phoneNumber = phoneNumber.slice(-10);
      }
      
      try {
        // Search lead by mobile
        const result = await apiCall(`leads.php?search=${phoneNumber}`, 'GET');
        
        console.log(`Deep Link: Search results for ${phoneNumber}:`, result.data?.length || 0, 'items');
        
        // Find exact match by comparing the last 10 digits accurately
        const exactMatch = result.success && result.data ? 
          result.data.find((l: any) => {
            if (!l.mobile) return false;
            // Clean both to last 10 digits for comparison
            const dbNum = l.mobile.replace(/[^0-9]/g, '').slice(-10);
            const searchNum = phoneNumber.replace(/[^0-9]/g, '').slice(-10);
            return dbNum === searchNum;
          }) : 
          null;

        if (exactMatch) {
          console.log('Deep Link: Found exact lead match:', exactMatch.name);
          router.push({
            pathname: '/lead-action',
            params: { 
              autoAction: 'update', 
              autoNumber: phoneNumber,
              leadId: exactMatch.id.toString(),
              leadName: exactMatch.name
            }
          });
        } else {
          console.log('Deep Link: No lead match found, opening Add Lead screen');
          router.push({
            pathname: '/lead-action',
            params: { autoAction: 'add', autoNumber: phoneNumber }
          });
        }
      } catch (e) {
        console.error('Error checking lead status via deep link:', e);
        // Fallback to add screen if check fails
        router.push({
          pathname: '/lead-action',
          params: { autoAction: 'add', autoNumber: phoneNumber }
        });
      }
    }
  };

  useEffect(() => {
    const sub = Linking.addEventListener('url', (event) => {
      handleDeepLink(event.url);
    });

    Linking.getInitialURL().then((url) => {
      if (url) handleDeepLink(url);
    });

    // Register background fetch task (15-min interval when app is in background)
    const bgTimer = setTimeout(() => {
      registerBackgroundSync();
    }, 5000);

    // Listen for app coming back to foreground from minimize/background
    const subscription = AppState.addEventListener('change', async (nextState) => {
      const prev = appState.current;
      appState.current = nextState;

      // Fire when transitioning background → active (minimize → focus)
      if ((prev === 'background' || prev === 'inactive') && nextState === 'active') {
        console.log('AppState: App returned to foreground — running silent sync');
        // Silent: no splash screen, no UI. Throttled by runAutoSync internally (5 min).
        await runAutoSync();
      }
    });

    return () => {
      clearTimeout(bgTimer);
      subscription.remove();
      sub.remove();
    };
  }, []);

  return (
    <SafeAreaProvider>
      <SnackbarProvider>
        <StatusBar style="dark" />
        <Stack screenOptions={{ headerShown: false }}>
          <Stack.Screen name="index" />
          <Stack.Screen name="(auth)/login" />
          <Stack.Screen name="(tabs)" />
          <Stack.Screen name="settings/recording" options={{ title: 'Recording Settings' }} />
        </Stack>
      </SnackbarProvider>
    </SafeAreaProvider>
  );
}
