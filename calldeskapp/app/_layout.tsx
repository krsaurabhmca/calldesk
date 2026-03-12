import React, { useEffect, useRef } from 'react';
import { AppState, AppStateStatus } from 'react-native';
import { Stack } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { SnackbarProvider } from "../context/SnackbarContext";
import { registerBackgroundSync } from "../services/backgroundSync";
import { runAutoSync } from "../services/autoSync";

export default function RootLayout() {
  const appState = useRef<AppStateStatus>(AppState.currentState);

  useEffect(() => {
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
    };
  }, []);

  return (
    <SnackbarProvider>
      <StatusBar style="dark" />
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="index" />
        <Stack.Screen name="(auth)/login" />
        <Stack.Screen name="(tabs)" />
        <Stack.Screen name="settings/recording" options={{ title: 'Recording Settings' }} />
      </Stack>
    </SnackbarProvider>
  );
}
