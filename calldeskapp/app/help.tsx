import React, { useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Platform } from 'react-native';
import { ShieldCheck, PhoneCall, Users, BarChart3, CloudSync, UserPlus, CalendarClock, MessageSquare, ChevronLeft, Languages, CheckCircle2 } from 'lucide-react-native';
import { useRouter } from 'expo-router';

export default function HelpScreen() {
    const router = useRouter();
    const [lang, setLang] = useState<'en' | 'hi'>('en');

    const content = {
        en: {
            header: "Help & Guide",
            whoTitle: "Who is Calldesk for?",
            whoDesc: "Business owners and sales teams who handle leads over the phone.",
            benefitsLabel: "Why Use Calldesk?",
            benefitsTitle: "Benefits for your Organization",
            howTitle: "How Calldesk Works",
            howLabel: "The Workflow",
            offlineText: "Calldesk works offline too! Your locally synced logs are pushed to the cloud once you have an internet connection.",
            back: "Back",
            roles: [
                { title: "Real Estate Agents", desc: "Track every inquiry from property portals." },
                { title: "Education / Schools", desc: "Manage admissions and parent inquiries." },
                { title: "Sales Teams", desc: "Ensure no follow-up is ever missed." },
                { title: "Consultants", desc: "Log client interactions automatically." }
            ],
            benefits: [
                { icon: ShieldCheck, color: "#10b981", title: "Zero Lead Leakage", desc: "Never lose a caller! Automatically sync every call from your business device to the CRM." },
                { icon: Users, color: "#6366f1", title: "Centralized Management", desc: "Keep all lead data in one place accessible to the whole team based on roles." },
                { icon: BarChart3, color: "#f59e0b", title: "Growth Analytics", desc: "Track executive performance, conversion rates, and team productivity." },
                { icon: MessageSquare, color: "#25D366", title: "Instant WhatsApp", desc: "Send templates directly from call logs or lead profiles." }
            ],
            steps: [
                { n: "1", t: "Sync Call Logs", d: "Tap 'Sync Logs' in the Calls tab to fetch recent calls from your device." },
                { n: "2", t: "Add Leads", d: "Identify new callers and add them as leads with one tap. Name is pre-filled from contacts." },
                { n: "3", t: "Update Status", d: "Mark interactions as Interested, Follow-up, or Converted with quick remarks." },
                { n: "4", t: "Follow-up", d: "Schedule future call reminders so you never forget a prospect." }
            ]
        },
        hi: {
            header: "मदद और गाइड",
            whoTitle: "कॉल्डेस्क किसके लिए है?",
            whoDesc: "उन बिज़नेस मालिकों और सेल्स टीमों के लिए जो कॉल पर लीड संभलते हैं।",
            benefitsLabel: "कॉल्डेस्क क्यों इस्तेमाल करें?",
            benefitsTitle: "आपके संगठन के लिए लाभ",
            howTitle: "कॉल्डेस्क कैसे काम करता है",
            howLabel: "कार्यप्रवाह (Workflow)",
            offlineText: "कॉल्डेस्क ऑफलाइन भी काम करता है! इंटरनेट आने पर आपका डेटा अपने आप क्लाउड पर सिंक हो जाएगा।",
            back: "पीछे",
            roles: [
                { title: "रियल एस्टेट एजेंट", desc: "प्रॉपर्टी पोर्टल से आने वाली हर पूछताछ पर नज़र रखें।" },
                { title: "शिक्षा / स्कूल", desc: "प्रवेश और माता-पिता की पूछताछ को मैनेज करें।" },
                { title: "सेल्स टीमें", desc: "सुनिश्चित करें कि कोई भी फॉलो-अप कभी न छूटे।" },
                { title: "सलाहकार (Consultants)", desc: "क्लाइंट के साथ होने वाली बातचीत को ऑटोमैटिक लॉग करें।" }
            ],
            benefits: [
                { icon: ShieldCheck, color: "#10b981", title: "ज़ीरो लीड लीकेज", desc: "किसी भी कॉलर को न खोएं! अपने डिवाइस से हर कॉल को CRM में ऑटोमैटिक सिंक करें।" },
                { icon: Users, color: "#6366f1", title: "केंद्रीकृत प्रबंधन", desc: "पूरे लीड डेटा को एक ही सुरक्षित जगह पर रखें।" },
                { icon: BarChart3, color: "#f59e0b", title: "ग्रोथ एनालिटिक्स", desc: "टीम के प्रदर्शन और कन्वर्शन रेट को विजुअल रिपोर्ट से ट्रैक करें।" },
                { icon: MessageSquare, color: "#25D366", title: "तुरंत व्हाट्सएप", desc: "कॉल लॉग से सीधे व्हाट्सएप टेम्पलेट भेजें।" }
            ],
            steps: [
                { n: "1", t: "कॉल लॉग सिंक करें", d: "डिवाइस से हालिया कॉल प्राप्त करने के लिए 'सिंक लॉग' पर टैप करें।" },
                { n: "2", t: "लीड जोड़ें", d: "नए कॉलर्स को पहचानें और उन्हें एक टैप में लीड के रूप में जोड़ें।" },
                { n: "3", t: "स्टेटस अपडेट करें", d: "बातचीत को इंटरेस्टेड या फॉलो-अप के रूप में मार्क करें।" },
                { n: "4", t: "फॉलो-अप याद दिलाएं", d: "अगली कॉल का समय तय करें ताकि आप किसी भी क्लाइंट को न भूलें।" }
            ]
        }
    };

    const t = content[lang];

    return (
        <View style={styles.container}>
            {/* Header */}
            <View style={styles.header}>
                <TouchableOpacity onPress={() => router.back()} style={styles.backBtn}>
                    <ChevronLeft size={24} color="#1e293b" />
                </TouchableOpacity>
                <Text style={styles.headerTitle}>{t.header}</Text>

                <TouchableOpacity
                    style={styles.langToggle}
                    onPress={() => setLang(lang === 'en' ? 'hi' : 'en')}
                >
                    <Languages size={18} color="#6366f1" />
                    <Text style={styles.langText}>{lang === 'en' ? 'हिन्दी' : 'English'}</Text>
                </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={styles.scrollContent} showsVerticalScrollIndicator={false}>
                {/* Who is it for */}
                <View style={styles.whoBox}>
                    <Text style={styles.sectionLabel}>{t.whoTitle}</Text>
                    <Text style={styles.whoDescText}>{t.whoDesc}</Text>
                    <View style={styles.rolesGrid}>
                        {t.roles.map((r, i) => (
                            <View key={i} style={styles.roleItem}>
                                <CheckCircle2 size={16} color="#6366f1" />
                                <View style={{ flex: 1 }}>
                                    <Text style={styles.roleTitle}>{r.title}</Text>
                                    <Text style={styles.roleDesc}>{r.desc}</Text>
                                </View>
                            </View>
                        ))}
                    </View>
                </View>

                {/* Benefits */}
                <Text style={[styles.sectionLabel, { marginTop: 32 }]}>{t.benefitsLabel}</Text>
                <Text style={styles.sectionTitle}>{t.benefitsTitle}</Text>

                {t.benefits.map((b, i) => (
                    <View key={i} style={styles.card}>
                        <View style={[styles.iconCircle, { backgroundColor: b.color + '15' }]}>
                            <b.icon size={24} color={b.color} />
                        </View>
                        <View style={styles.cardContent}>
                            <Text style={styles.cardTitle}>{b.title}</Text>
                            <Text style={styles.cardDesc}>{b.desc}</Text>
                        </View>
                    </View>
                ))}

                {/* How it Works */}
                <View style={styles.howItWorksBox}>
                    <Text style={styles.sectionLabel}>{t.howLabel}</Text>
                    <Text style={styles.sectionTitle}>{t.howTitle}</Text>

                    {t.steps.map((s, i) => (
                        <View key={i} style={styles.stepRow}>
                            <View style={styles.stepLeft}>
                                <View style={styles.stepLineTop} />
                                <View style={styles.stepNumber}>
                                    <Text style={styles.stepNumberText}>{s.n}</Text>
                                </View>
                                {i !== t.steps.length - 1 && <View style={styles.stepLineBottom} />}
                            </View>
                            <View style={styles.stepRight}>
                                <Text style={styles.stepTitle}>{s.t}</Text>
                                <Text style={styles.stepDesc}>{s.d}</Text>
                            </View>
                        </View>
                    ))}
                </View>

                <View style={styles.infoBox}>
                    <CloudSync size={20} color="#6366f1" />
                    <Text style={styles.infoText}>{t.offlineText}</Text>
                </View>
            </ScrollView>
        </View>
    );
}

const styles = StyleSheet.create({
    container: { flex: 1, backgroundColor: '#f8fafc' },
    header: { flexDirection: 'row', alignItems: 'center', padding: 16, backgroundColor: '#fff', borderBottomWidth: 1, borderBottomColor: '#f1f5f9', paddingTop: Platform.OS === 'ios' ? 50 : 20 },
    backBtn: { padding: 8, marginRight: 8 },
    headerTitle: { fontSize: 18, fontWeight: '800', color: '#1e293b', flex: 1 },
    langToggle: { flexDirection: 'row', alignItems: 'center', gap: 6, backgroundColor: '#f1f5f9', paddingHorizontal: 12, paddingVertical: 6, borderRadius: 10 },
    langText: { fontSize: 12, fontWeight: '700', color: '#6366f1' },
    scrollContent: { padding: 20 },
    sectionLabel: { fontSize: 12, fontWeight: '700', color: '#6366f1', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 4 },
    sectionTitle: { fontSize: 22, fontWeight: '800', color: '#0f172a', marginBottom: 20 },
    whoBox: { backgroundColor: '#fff', borderRadius: 24, padding: 20, borderWidth: 1, borderColor: '#f1f5f9' },
    whoDescText: { fontSize: 15, color: '#475569', marginBottom: 20, lineHeight: 22 },
    rolesGrid: { gap: 16 },
    roleItem: { flexDirection: 'row', gap: 12, alignItems: 'flex-start' },
    roleTitle: { fontSize: 15, fontWeight: '700', color: '#1e293b' },
    roleDesc: { fontSize: 13, color: '#64748b', marginTop: 2 },
    card: { flexDirection: 'row', backgroundColor: '#fff', padding: 16, borderRadius: 20, marginBottom: 12, borderWidth: 1, borderColor: '#f1f5f9', elevation: 2, shadowColor: '#000', shadowOffset: { width: 0, height: 2 }, shadowOpacity: 0.05, shadowRadius: 10 },
    iconCircle: { width: 48, height: 48, borderRadius: 14, justifyContent: 'center', alignItems: 'center' },
    cardContent: { flex: 1, marginLeft: 16 },
    cardTitle: { fontSize: 16, fontWeight: '700', color: '#1e293b', marginBottom: 4 },
    cardDesc: { fontSize: 13, color: '#64748b', lineHeight: 18 },
    howItWorksBox: { marginTop: 32, backgroundColor: '#fff', borderRadius: 24, padding: 24, borderWidth: 1, borderColor: '#f1f5f9' },
    stepRow: { flexDirection: 'row' },
    stepLeft: { width: 30, alignItems: 'center' },
    stepLineTop: { width: 2, height: 10, backgroundColor: '#e2e8f0' },
    stepLineBottom: { flex: 1, width: 2, backgroundColor: '#e2e8f0' },
    stepNumber: { width: 24, height: 24, borderRadius: 12, backgroundColor: '#6366f1', justifyContent: 'center', alignItems: 'center', zIndex: 1 },
    stepNumberText: { color: '#fff', fontSize: 12, fontWeight: '800' },
    stepRight: { flex: 1, marginLeft: 16, paddingBottom: 24 },
    stepTitle: { fontSize: 16, fontWeight: '700', color: '#1e293b', marginBottom: 4 },
    stepDesc: { fontSize: 13, color: '#64748b', lineHeight: 18 },
    infoBox: { flexDirection: 'row', backgroundColor: '#f5f3ff', padding: 16, borderRadius: 16, marginTop: 24, alignItems: 'center', gap: 12, borderWidth: 1, borderColor: '#e0e7ff' },
    infoText: { flex: 1, fontSize: 12, color: '#6366f1', fontWeight: '600', lineHeight: 18 }
});
