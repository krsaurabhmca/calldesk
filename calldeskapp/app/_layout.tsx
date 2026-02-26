import { Stack } from "expo-router";
import { StatusBar } from "expo-status-bar";
import { SnackbarProvider } from "../context/SnackbarContext";

export default function RootLayout() {
  return (
    <SnackbarProvider>
      <StatusBar style="dark" />
      <Stack screenOptions={{ headerShown: false }}>
        <Stack.Screen name="index" />
        <Stack.Screen name="(auth)/login" />
        <Stack.Screen name="(tabs)" />
      </Stack>
    </SnackbarProvider>
  );
}
