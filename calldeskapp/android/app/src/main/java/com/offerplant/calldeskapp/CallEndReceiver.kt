package com.offerplant.calldeskapp

import android.app.PendingIntent
import android.content.BroadcastReceiver
import android.content.Context
import android.content.Intent
import android.os.Build
import android.os.PowerManager
import android.telephony.TelephonyManager
import android.util.Log

class CallEndReceiver : BroadcastReceiver() {
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
                    // Clear it so we don't accidentally re-trigger
                    editor.apply()
                    launchApp(context, lastNum)
                }
            }
        }
    }

    private fun launchApp(context: Context, phoneNumber: String?) {
        try {
            var cleanNumber = phoneNumber?.replace("[^0-9]".toRegex(), "") ?: ""
            // Ensure 10-digit Indian mobile number by taking the last 10 digits
            if (cleanNumber.length > 10) {
                cleanNumber = cleanNumber.takeLast(10)
            }
            Log.d("CallEndReceiver", "Launching app via Deep Link for: $cleanNumber")
            
            val powerManager = context.getSystemService(Context.POWER_SERVICE) as PowerManager
            val wakeLock = powerManager.newWakeLock(
                PowerManager.FULL_WAKE_LOCK or 
                PowerManager.ACQUIRE_CAUSES_WAKEUP or 
                PowerManager.ON_AFTER_RELEASE, 
                "CallDeskApp::WakeLock"
            )
            wakeLock.acquire(3000)

            // Use Deep Link to trigger the app and pass the number via root to avoid Unmatched Route
            val deepLinkUri = android.net.Uri.parse("calldeskapp://?reason=call_ended&number=$cleanNumber")
            val intent = Intent(Intent.ACTION_VIEW, deepLinkUri)
            
            intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK or 
                           Intent.FLAG_ACTIVITY_REORDER_TO_FRONT or 
                           Intent.FLAG_ACTIVITY_SINGLE_TOP)
            
            context.startActivity(intent)
            Log.d("CallEndReceiver", "Deep Link launch successful.")

        } catch (e: Exception) {
            Log.e("CallEndReceiver", "Deep Link launch failed, trying explicit launch: ${e.message}")
            try {
                // Fallback to explicit MainActivity
                val intent = Intent(context, MainActivity::class.java)
                intent.putExtra("phoneNumber", phoneNumber)
                intent.putExtra("reason", "call_ended")
                intent.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                context.startActivity(intent)
            } catch (ex: Exception) {
                Log.e("CallEndReceiver", "Total launch failure: ${ex.message}")
            }
        }
    }
}

