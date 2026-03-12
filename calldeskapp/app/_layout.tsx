import React, { useEffect } from 'react';
import { Stack } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { SnackbarProvider } from "../context/SnackbarContext";
import { registerBackgroundSync } from "../services/backgroundSync";

export default function RootLayout() {
  useEffect(() => {
    // Register background fetch task so sync continues every 15 min in background
    const timer = setTimeout(() => {
      registerBackgroundSync();
    }, 5000);
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
