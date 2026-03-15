package com.offerplant.calldeskapp

import android.app.NotificationChannel
import android.app.NotificationManager
import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.media.AudioAttributes
import android.media.RingtoneManager
import android.net.Uri
import android.os.Build
import android.os.PowerManager
import android.telephony.TelephonyManager
import android.util.Log
import androidx.core.app.NotificationCompat

class CallEndReceiver : BroadcastReceiver() {
    companion object {
        private const val CHANNEL_ID = "call_events"
        private const val NOTIFICATION_ID = 1001
    }

    override fun onReceive(context: Context, intent: Intent) {
        if (intent.action == TelephonyManager.ACTION_PHONE_STATE_CHANGED) {
            val stateStr = intent.getStringExtra(TelephonyManager.EXTRA_STATE)
            val incomingNumber = intent.getStringExtra(TelephonyManager.EXTRA_INCOMING_NUMBER)
            
            Log.d("CallEndReceiver", "Phone state: $stateStr, Number: $incomingNumber")

            val prefs = context.getSharedPreferences("CallDeskPrefs", Context.MODE_PRIVATE)
            val editor = prefs.edit()

            if (incomingNumber != null) {
                editor.putString("lastPhoneNumber", incomingNumber)
                editor.apply()
            }

            if (stateStr == TelephonyManager.EXTRA_STATE_RINGING || stateStr == TelephonyManager.EXTRA_STATE_OFFHOOK) {
                editor.putBoolean("isCallActive", true)
                editor.apply()
            } else if (stateStr == TelephonyManager.EXTRA_STATE_IDLE) {
                val wasActive = prefs.getBoolean("isCallActive", false)
                val lastNum = prefs.getString("lastPhoneNumber", "")
                
                if (wasActive) {
                    editor.putBoolean("isCallActive", false)
                    editor.apply()
                    Log.d("CallEndReceiver", "Call finished. Launching app with high priority.")
                    notifyAndLaunch(context, lastNum)
                }
            }
        }
    }

    private fun notifyAndLaunch(context: Context, phoneNumber: String?) {
        var cleanNumber = phoneNumber?.replace("[^0-9]".toRegex(), "") ?: ""
        if (cleanNumber.length > 10) {
            cleanNumber = cleanNumber.takeLast(10)
        }

        val deepLinkUri = Uri.parse("calldeskapp://?reason=call_ended&number=$cleanNumber")
        val launchIntent = Intent(Intent.ACTION_VIEW, deepLinkUri).apply {
            addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or 
                     Intent.FLAG_ACTIVITY_REORDER_TO_FRONT or 
                     Intent.FLAG_ACTIVITY_SINGLE_TOP)
        }

        val pendingIntent = PendingIntent.getActivity(
            context, 
            0, 
            launchIntent, 
            PendingIntent.FLAG_UPDATE_CURRENT or PendingIntent.FLAG_IMMUTABLE
        )

        // Ensure App is awake
        val powerManager = context.getSystemService(Context.POWER_SERVICE) as PowerManager
        val wakeLock = powerManager.newWakeLock(
            PowerManager.FULL_WAKE_LOCK or 
            PowerManager.ACQUIRE_CAUSES_WAKEUP or 
            PowerManager.ON_AFTER_RELEASE, 
            "CallDeskApp::WakeLock"
        )
        wakeLock.acquire(10000)

        // 1. Create Notification Channel
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.O) {
            val name = "Call Events"
            val descriptionText = "Notifications triggered after phone calls"
            val importance = NotificationManager.IMPORTANCE_HIGH
            val channel = NotificationChannel(CHANNEL_ID, name, importance).apply {
                description = descriptionText
                lockscreenVisibility = NotificationCompat.VISIBILITY_PUBLIC
                enableVibration(true)
            }
            val notificationManager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
            notificationManager.createNotificationChannel(channel)
        }

        // 2. Build High-Priority Notification with Full Screen Intent (Guarantees launch on modern Android)
        val builder = NotificationCompat.Builder(context, CHANNEL_ID)
            .setSmallIcon(android.R.drawable.sym_call_missed) // Use built-in icon for now
            .setContentTitle("Call Finished: $cleanNumber")
            .setContentText("Tap to update lead interaction")
            .setPriority(NotificationCompat.PRIORITY_MAX)
            .setCategory(NotificationCompat.CATEGORY_CALL)
            .setVisibility(NotificationCompat.VISIBILITY_PUBLIC)
            .setAutoCancel(true)
            .setContentIntent(pendingIntent)
            .setFullScreenIntent(pendingIntent, true) // Key for auto-opening from background/locked

        val notificationManager = context.getSystemService(Context.NOTIFICATION_SERVICE) as NotificationManager
        notificationManager.notify(NOTIFICATION_ID, builder.build())

        // Also try direct launch as backup
        try {
            context.startActivity(launchIntent)
        } catch (e: Exception) {
            Log.e("CallEndReceiver", "Direct launch failed: ${e.message}")
        }
    }
}

