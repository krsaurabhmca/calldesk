import React, { useEffect } from 'react';
import { Stack } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { SnackbarProvider } from "../context/SnackbarContext";
import { registerBackgroundSync } from "../services/backgroundSync";
import { runAutoSync } from "../services/autoSync";

export default function RootLayout() {
  useEffect(() => {
    // Delay slightly so the app UI fully loads first
    const timer = setTimeout(async () => {
      // 1. Register background fetch task (runs when app is in background/closed)
      registerBackgroundSync();

      // 2. Run auto-sync silently right now (on foreground open)
      await runAutoSync();
    }, 3000);

    return () => clearTimeout(timer);
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
