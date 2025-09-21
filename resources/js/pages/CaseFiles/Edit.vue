<template>
    <Head title="Fall bearbeiten" />

    <AppLayout>
        <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/40 py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <!-- Spectacular Header with Glassmorphism -->
                <div class="mb-8 rounded-2xl bg-white/80 backdrop-blur-sm border border-white/20 shadow-xl p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-6">
                            <div class="relative">
                                <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 rounded-xl blur opacity-30"></div>
                                <div class="relative bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-4 rounded-xl">
                                    <Edit class="h-6 w-6 mb-2" />
                                    <h1 class="text-2xl font-bold">Fall bearbeiten</h1>
                                </div>
                            </div>
                            <div class="space-y-1">
                                <h2 class="text-xl font-semibold text-slate-800">{{ caseFile?.case_number }}</h2>
                                <p class="text-slate-600">{{ caseFile?.title }}</p>
                                <div class="flex items-center gap-2">
                                    <Badge :variant="getStatusVariant(caseFile?.status)" class="text-xs">
                                        <component :is="getStatusIcon(caseFile?.status)" class="mr-1 h-3 w-3" />
                                        {{ formatStatus(caseFile?.status) }}
                                    </Badge>
                                    <Progress :value="getStatusProgress(caseFile?.status)" class="w-24 h-2" />
                                </div>
                            </div>
                        </div>
                        <Button variant="outline" as-child class="backdrop-blur-sm bg-white/60 hover:bg-white/80 border-white/30">
                            <Link :href="caseReference?.id ? show.url({ case: caseReference.id }) : '#'">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Zurück zum Fall
                            </Link>
                        </Button>
                    </div>
                </div>

                <!-- Tabbed Interface for Edit Form -->
                <Tabs default-value="basic" class="space-y-6">
                    <TabsList class="grid w-full grid-cols-4 bg-white/80 backdrop-blur-sm border border-white/20 shadow-lg">
                        <TabsTrigger value="basic" class="flex items-center gap-2">
                            <FileText class="h-4 w-4" />
                            Grunddaten
                        </TabsTrigger>
                        <TabsTrigger value="status" class="flex items-center gap-2">
                            <BarChart3 class="h-4 w-4" />
                            Status & Verlauf
                        </TabsTrigger>
                        <TabsTrigger value="database" class="flex items-center gap-2">
                            <Database class="h-4 w-4" />
                            Datenbank
                        </TabsTrigger>
                        <TabsTrigger value="advanced" class="flex items-center gap-2">
                            <Settings class="h-4 w-4" />
                            Erweitert
                        </TabsTrigger>
                    </TabsList>

                    <form @submit.prevent="submitForm" class="space-y-6">
                        <!-- Basic Information Tab -->
                        <TabsContent value="basic" class="space-y-6">
                            <Card class="backdrop-blur-sm bg-white/90 border-white/30 shadow-xl">
                                <CardHeader class="bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-white/20">
                                    <CardTitle class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-100 rounded-lg">
                                            <FileText class="h-5 w-5 text-blue-600" />
                                        </div>
                                        Grundlegende Fallinformationen
                                    </CardTitle>
                                    <CardDescription>
                                        Bearbeiten Sie die wichtigsten Identifikationsmerkmale des Falls
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-8 space-y-8">
                                    <!-- Case Number with Enhanced Styling -->
                                    <div class="space-y-3">
                                        <Label for="case_number" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <Hash class="h-4 w-4" />
                                            Aktenzeichen
                                            <span class="text-red-500">*</span>
                                            <Badge variant="secondary" class="ml-auto text-xs">Eindeutig</Badge>
                                        </Label>
                                        <div class="relative">
                                            <Input
                                                id="case_number"
                                                v-model="form.case_number"
                                                placeholder="ARB-2024-001"
                                                required
                                                class="pl-10 h-12 text-lg font-mono bg-white/80 border-slate-200 focus:border-blue-400 focus:ring-blue-400/20"
                                            />
                                            <Hash class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                        </div>
                                        <p v-if="form.errors.case_number" class="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle class="h-4 w-4" />
                                            {{ form.errors.case_number }}
                                        </p>
                                        <p class="text-sm text-slate-500 flex items-center gap-1">
                                            <Info class="h-4 w-4" />
                                            Eindeutige Kennzeichnung des Schiedsverfahrens (z.B. ARB-2024-001)
                                        </p>
                                    </div>

                                    <!-- Title with Enhanced Styling -->
                                    <div class="space-y-3">
                                        <Label for="title" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <Type class="h-4 w-4" />
                                            Fallbezeichnung
                                            <span class="text-red-500">*</span>
                                            <Badge variant="secondary" class="ml-auto text-xs">Öffentlich</Badge>
                                        </Label>
                                        <div class="relative">
                                            <Input
                                                id="title"
                                                v-model="form.title"
                                                placeholder="Streit zwischen Firma A und Firma B bezüglich Vertragsverletzung"
                                                required
                                                class="pl-10 h-12 text-lg bg-white/80 border-slate-200 focus:border-blue-400 focus:ring-blue-400/20"
                                            />
                                            <Type class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
                                        </div>
                                        <p v-if="form.errors.title" class="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle class="h-4 w-4" />
                                            {{ form.errors.title }}
                                        </p>
                                        <p class="text-sm text-slate-500 flex items-center gap-1">
                                            <Info class="h-4 w-4" />
                                            Kurze, prägnante Beschreibung des Streitgegenstands
                                        </p>
                                    </div>

                                    <!-- Case Summary -->
                                    <div class="space-y-3">
                                        <Label for="description" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <FileText class="h-4 w-4" />
                                            Kurzbeschreibung
                                            <Badge variant="outline" class="ml-auto text-xs">Optional</Badge>
                                        </Label>
                                        <Textarea
                                            id="description"
                                            v-model="form.description"
                                            placeholder="Detaillierte Beschreibung des Sachverhalts und der strittigen Punkte..."
                                            rows="4"
                                            class="bg-white/80 border-slate-200 focus:border-blue-400 focus:ring-blue-400/20"
                                        />
                                        <p class="text-sm text-slate-500 flex items-center gap-1">
                                            <Info class="h-4 w-4" />
                                            Optionale ausführliche Beschreibung des Falls
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Status & Progress Tab -->
                        <TabsContent value="status" class="space-y-6">
                            <Card class="backdrop-blur-sm bg-white/90 border-white/30 shadow-xl">
                                <CardHeader class="bg-gradient-to-r from-green-50 to-emerald-50 border-b border-white/20">
                                    <CardTitle class="flex items-center gap-3">
                                        <div class="p-2 bg-green-100 rounded-lg">
                                            <BarChart3 class="h-5 w-5 text-green-600" />
                                        </div>
                                        Verfahrensstatus & Fortschritt
                                    </CardTitle>
                                    <CardDescription>
                                        Verwalten Sie den aktuellen Status und verfolgen Sie den Verfahrensfortschritt
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-8 space-y-8">
                                    <!-- Current Status -->
                                    <div class="space-y-4">
                                        <Label for="status" class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <TrendingUp class="h-4 w-4" />
                                            Verfahrensstatus
                                            <Badge :variant="getStatusVariant(form.status)" class="ml-auto">
                                                <component :is="getStatusIcon(form.status)" class="mr-1 h-3 w-3" />
                                                {{ formatStatus(form.status) }}
                                            </Badge>
                                        </Label>
                                        <Select v-model="form.status">
                                            <SelectTrigger class="h-12 bg-white/80 border-slate-200">
                                                <SelectValue placeholder="Status auswählen" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="draft" class="flex items-center gap-2">
                                                    <FileEdit class="h-4 w-4" />
                                                    Entwurf
                                                </SelectItem>
                                                <SelectItem value="active" class="flex items-center gap-2">
                                                    <Play class="h-4 w-4" />
                                                    Aktiv
                                                </SelectItem>
                                                <SelectItem value="initiated" class="flex items-center gap-2">
                                                    <Rocket class="h-4 w-4" />
                                                    Eingeleitet
                                                </SelectItem>
                                                <SelectItem value="pending" class="flex items-center gap-2">
                                                    <Clock class="h-4 w-4" />
                                                    Wartend
                                                </SelectItem>
                                                <SelectItem value="hearing_scheduled" class="flex items-center gap-2">
                                                    <Calendar class="h-4 w-4" />
                                                    Anhörung geplant
                                                </SelectItem>
                                                <SelectItem value="under_deliberation" class="flex items-center gap-2">
                                                    <Brain class="h-4 w-4" />
                                                    In Beratung
                                                </SelectItem>
                                                <SelectItem value="suspended" class="flex items-center gap-2">
                                                    <Pause class="h-4 w-4" />
                                                    Ausgesetzt
                                                </SelectItem>
                                                <SelectItem value="settled" class="flex items-center gap-2">
                                                    <Handshake class="h-4 w-4" />
                                                    Verglichen
                                                </SelectItem>
                                                <SelectItem value="decided" class="flex items-center gap-2">
                                                    <CheckCircle class="h-4 w-4" />
                                                    Entschieden
                                                </SelectItem>
                                                <SelectItem value="closed" class="flex items-center gap-2">
                                                    <Archive class="h-4 w-4" />
                                                    Geschlossen
                                                </SelectItem>
                                            </SelectContent>
                                        </Select>
                                        <p v-if="form.errors.status" class="text-sm text-red-600 flex items-center gap-1">
                                            <AlertCircle class="h-4 w-4" />
                                            {{ form.errors.status }}
                                        </p>
                                    </div>

                                    <!-- Progress Visualization -->
                                    <div class="space-y-4">
                                        <Label class="flex items-center gap-2 text-sm font-semibold text-slate-700">
                                            <Activity class="h-4 w-4" />
                                            Verfahrensfortschritt
                                        </Label>
                                        <div class="bg-gradient-to-r from-slate-50 to-blue-50 rounded-xl p-6 border border-slate-200">
                                            <div class="flex items-center justify-between mb-4">
                                                <span class="text-sm font-medium text-slate-600">Aktuelle Phase</span>
                                                <span class="text-lg font-bold text-blue-600">{{ getStatusProgress(form.status) }}%</span>
                                            </div>
                                            <Progress :value="getStatusProgress(form.status)" class="h-3 mb-4" />
                                            <div class="grid grid-cols-2 gap-4 text-sm">
                                                <div>
                                                    <span class="text-slate-500">Nächster Schritt:</span>
                                                    <p class="font-medium text-slate-700">{{ getNextStep(form.status) }}</p>
                                                </div>
                                                <div>
                                                    <span class="text-slate-500">Geschätzte Dauer:</span>
                                                    <p class="font-medium text-slate-700">{{ getEstimatedDuration(form.status) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Database Information Tab -->
                        <TabsContent value="database" class="space-y-6">
                            <Card class="backdrop-blur-sm bg-white/90 border-white/30 shadow-xl">
                                <CardHeader class="bg-gradient-to-r from-purple-50 to-violet-50 border-b border-white/20">
                                    <CardTitle class="flex items-center gap-3">
                                        <div class="p-2 bg-purple-100 rounded-lg">
                                            <Database class="h-5 w-5 text-purple-600" />
                                        </div>
                                        Datenbank-Konfiguration
                                    </CardTitle>
                                    <CardDescription>
                                        Technische Informationen zur dedizierten Falldatenbank
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-8">
                                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        <div class="space-y-4">
                                            <div class="bg-gradient-to-br from-slate-50 to-blue-50 rounded-xl p-4 border border-slate-200">
                                                <Label class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-2">
                                                    <Database class="h-3 w-3" />
                                                    Datenbank Name
                                                </Label>
                                                <p class="text-sm font-mono font-semibold text-slate-800 bg-white/60 px-3 py-2 rounded-lg">
                                                    {{ caseReference.database_name || 'Nicht verfügbar' }}
                                                </p>
                                            </div>
                                            <div class="bg-gradient-to-br from-slate-50 to-green-50 rounded-xl p-4 border border-slate-200">
                                                <Label class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-2">
                                                    <Link2 class="h-3 w-3" />
                                                    Verbindungsname
                                                </Label>
                                                <p class="text-sm font-mono font-semibold text-slate-800 bg-white/60 px-3 py-2 rounded-lg">
                                                    {{ caseReference.connection_name || 'Nicht verfügbar' }}
                                                </p>
                                            </div>
                                        </div>
                                        <div class="space-y-4">
                                            <div class="bg-gradient-to-br from-slate-50 to-amber-50 rounded-xl p-4 border border-slate-200">
                                                <Label class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-2">
                                                    <Calendar class="h-3 w-3" />
                                                    Erstellt am
                                                </Label>
                                                <p class="text-sm font-semibold text-slate-800 bg-white/60 px-3 py-2 rounded-lg">
                                                    {{ formatDate(caseReference.created_at) }}
                                                </p>
                                            </div>
                                            <div class="bg-gradient-to-br from-slate-50 to-indigo-50 rounded-xl p-4 border border-slate-200">
                                                <Label class="flex items-center gap-2 text-xs font-medium text-slate-500 mb-2">
                                                    <Key class="h-3 w-3" />
                                                    Tenant Case ID
                                                </Label>
                                                <p class="text-sm font-mono font-semibold text-slate-800 bg-white/60 px-3 py-2 rounded-lg">
                                                    {{ caseReference.tenant_case_id || 'Nicht gesetzt' }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Database Status -->
                                    <div class="mt-6 p-4 bg-gradient-to-r from-green-50 to-emerald-50 rounded-xl border border-green-200">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100">
                                                <CheckCircle class="h-4 w-4 text-green-600" />
                                            </div>
                                            <div>
                                                <h4 class="text-sm font-semibold text-green-800">Datenbank aktiv</h4>
                                                <p class="text-sm text-green-600">Die dedizierte Falldatenbank ist verbunden und einsatzbereit</p>
                                            </div>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Advanced Settings Tab -->
                        <TabsContent value="advanced" class="space-y-6">
                            <Card class="backdrop-blur-sm bg-white/90 border-white/30 shadow-xl">
                                <CardHeader class="bg-gradient-to-r from-orange-50 to-red-50 border-b border-white/20">
                                    <CardTitle class="flex items-center gap-3">
                                        <div class="p-2 bg-orange-100 rounded-lg">
                                            <Settings class="h-5 w-5 text-orange-600" />
                                        </div>
                                        Erweiterte Einstellungen
                                    </CardTitle>
                                    <CardDescription>
                                        Zusätzliche Konfigurationen und Metadaten
                                    </CardDescription>
                                </CardHeader>
                                <CardContent class="p-8 space-y-6">
                                    <!-- Coming Soon Notice -->
                                    <div class="text-center py-12">
                                        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-full mb-4">
                                            <Settings class="h-8 w-8 text-blue-600" />
                                        </div>
                                        <h3 class="text-lg font-semibold text-slate-700 mb-2">Erweiterte Features in Entwicklung</h3>
                                        <p class="text-slate-500 max-w-md mx-auto">
                                            Zusätzliche Konfigurationsoptionen, Metadaten-Verwaltung und erweiterte Falleinstellungen werden bald verfügbar sein.
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </TabsContent>

                        <!-- Success Message -->
                        <div v-if="form.recentlySuccessful" class="rounded-xl bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 p-6 shadow-lg">
                            <div class="flex items-center gap-4">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                    <Check class="h-5 w-5 text-green-600" />
                                </div>
                                <div>
                                    <h4 class="text-lg font-semibold text-green-800">Erfolgreich gespeichert!</h4>
                                    <p class="text-sm text-green-600">Alle Änderungen wurden erfolgreich in der Falldatenbank gespeichert.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Form Actions -->
                        <div class="flex items-center justify-between pt-8">
                            <Button variant="outline" as-child class="h-12 px-6 bg-white/80 backdrop-blur-sm border-slate-200 hover:bg-white/90">
                                <Link :href="caseReference?.id ? show.url({ case: caseReference.id }) : '#'">
                                    <X class="mr-2 h-4 w-4" />
                                    Abbrechen
                                </Link>
                            </Button>
                            <Button type="submit" :disabled="form.processing" class="h-12 px-8 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white shadow-lg">
                                <Save v-if="!form.processing" class="mr-2 h-4 w-4" />
                                <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></div>
                                {{ form.processing ? 'Speichern...' : 'Änderungen speichern' }}
                            </Button>
                        </div>
                    </form>
                </Tabs>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs'
import Textarea from '@/components/ui/textarea.vue'
import { Badge } from '@/components/ui/badge'
import { Progress } from '@/components/ui/progress'
import {
    ArrowLeft, Edit, Save, Check, X, FileText, BarChart3, Database, Settings,
    Hash, Type, Info, AlertCircle, TrendingUp, Activity, Calendar, Key,
    CheckCircle, Link2, FileEdit, Play, Rocket, Clock, Brain, Pause,
    Handshake, Archive
} from 'lucide-vue-next'
import { show, update } from '@/routes/cases'

const props = defineProps({
    caseFile: Object,
    caseReference: Object,
})

const form = useForm({
    case_number: props.caseFile?.case_number || '',
    title: props.caseFile?.title || '',
    status: props.caseFile?.status || 'active',
    description: props.caseFile?.description || '',
})

const submitForm = () => {
    if (!props.caseReference?.id) {
        console.error('No case reference ID available for form submission')
        return
    }
    console.log('Submitting form with case ID:', props.caseReference.id)
    form.put(update.url({ case: props.caseReference.id }))
}

const formatDate = (dateString) => {
    if (!dateString) return '-'
    return new Date(dateString).toLocaleDateString('de-DE', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getStatusVariant = (status: string) => {
    const variants = {
        'draft': 'secondary',
        'active': 'default',
        'initiated': 'default',
        'pending': 'outline',
        'hearing_scheduled': 'secondary',
        'under_deliberation': 'outline',
        'suspended': 'destructive',
        'settled': 'default',
        'decided': 'default',
        'closed': 'secondary'
    }
    return variants[status] || 'outline'
}

const getStatusIcon = (status: string) => {
    const icons = {
        'draft': FileEdit,
        'active': Play,
        'initiated': Rocket,
        'pending': Clock,
        'hearing_scheduled': Calendar,
        'under_deliberation': Brain,
        'suspended': Pause,
        'settled': Handshake,
        'decided': CheckCircle,
        'closed': Archive
    }
    return icons[status] || Clock
}

const formatStatus = (status: string) => {
    const statusLabels = {
        'draft': 'Entwurf',
        'active': 'Aktiv',
        'initiated': 'Eingeleitet',
        'pending': 'Wartend',
        'hearing_scheduled': 'Anhörung geplant',
        'under_deliberation': 'In Beratung',
        'suspended': 'Ausgesetzt',
        'settled': 'Verglichen',
        'decided': 'Entschieden',
        'closed': 'Geschlossen'
    }
    return statusLabels[status] || status
}

const getStatusProgress = (status: string) => {
    const progress = {
        'draft': 5,
        'active': 15,
        'initiated': 25,
        'pending': 35,
        'hearing_scheduled': 50,
        'under_deliberation': 75,
        'suspended': 40,
        'settled': 95,
        'decided': 95,
        'closed': 100
    }
    return progress[status] || 0
}

const getNextStep = (status: string) => {
    const nextSteps = {
        'draft': 'Verfahren einleiten',
        'active': 'Parteien bestellen',
        'initiated': 'Schiedsrichter ernennen',
        'pending': 'Anhörung terminieren',
        'hearing_scheduled': 'Anhörung durchführen',
        'under_deliberation': 'Entscheidung treffen',
        'suspended': 'Verfahren fortsetzen',
        'settled': 'Vergleich dokumentieren',
        'decided': 'Entscheidung vollstrecken',
        'closed': 'Archivierung'
    }
    return nextSteps[status] || 'Nächster Schritt definieren'
}

const getEstimatedDuration = (status: string) => {
    const durations = {
        'draft': '1-2 Wochen',
        'active': '2-4 Wochen',
        'initiated': '3-6 Wochen',
        'pending': '1-3 Wochen',
        'hearing_scheduled': '2-8 Wochen',
        'under_deliberation': '4-12 Wochen',
        'suspended': 'Unbestimmt',
        'settled': '1-2 Wochen',
        'decided': '2-4 Wochen',
        'closed': 'Abgeschlossen'
    }
    return durations[status] || 'Unbekannt'
}
</script>