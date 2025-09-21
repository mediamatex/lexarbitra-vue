<template>
    <Head :title="`Fall ${caseFile.case_number}`" />

    <AppLayout>
        <div class="py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">{{ caseFile.case_number }}</h2>
                        <p class="text-muted-foreground">
                            {{ caseFile.title }}
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="index.url()">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Zurück zu den Fällen
                            </Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link :href="edit.url({ case: caseFile.id })">
                                <Edit class="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Link>
                        </Button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Case Details -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="flex items-center gap-2">
                                    <FileText class="h-5 w-5" />
                                    Falldaten
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Aktenzeichen</Label>
                                        <p class="text-lg font-semibold">{{ caseFile.case_number }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Status</Label>
                                        <div class="mt-1">
                                            <Badge :variant="getStatusVariant(caseFile.status)">
                                                {{ formatStatus(caseFile.status) }}
                                            </Badge>
                                        </div>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Titel</Label>
                                        <p class="text-lg">{{ caseFile.title }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-sm font-medium text-muted-foreground">Initiiert am</Label>
                                        <p class="text-lg">{{ formatDate(caseFile.initiated_at) }}</p>
                                    </div>
                                </div>

                                <!-- Tenant Case Data (if available) -->
                                <div v-if="caseFile.description" class="pt-4 border-t">
                                    <Label class="text-sm font-medium text-muted-foreground">Beschreibung</Label>
                                    <p class="mt-2 text-sm leading-relaxed">{{ caseFile.description }}</p>
                                </div>

                                <!-- Additional tenant data fields -->
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 pt-4 border-t">
                                    <div v-if="caseFile.dispute_value">
                                        <Label class="text-sm font-medium text-muted-foreground">Streitwert</Label>
                                        <p class="text-lg">{{ formatCurrency(caseFile.dispute_value, caseFile.currency) }}</p>
                                    </div>
                                    <div v-if="caseFile.jurisdiction">
                                        <Label class="text-sm font-medium text-muted-foreground">Zuständigkeit</Label>
                                        <p class="text-lg">{{ caseFile.jurisdiction }}</p>
                                    </div>
                                    <div v-if="caseFile.case_category">
                                        <Label class="text-sm font-medium text-muted-foreground">Kategorie</Label>
                                        <p class="text-lg">{{ caseFile.case_category }}</p>
                                    </div>
                                    <div v-if="caseFile.complexity_level">
                                        <Label class="text-sm font-medium text-muted-foreground">Komplexität</Label>
                                        <p class="text-lg">{{ caseFile.complexity_level }}</p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                    </div>

                    <!-- Sidebar -->
                    <div class="space-y-6">
                        <!-- Quick Actions -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg">Aktionen</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-3">
                                <Button variant="outline" class="w-full justify-start" as-child>
                                    <Link :href="edit.url({ case: caseFile.id })">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Fall bearbeiten
                                    </Link>
                                </Button>
                                <Button
                                    variant="destructive"
                                    class="w-full justify-start"
                                    @click="deleteCase"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Fall löschen
                                </Button>
                            </CardContent>
                        </Card>

                        <!-- Case Info -->
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-lg">Fall-Informationen</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div>
                                    <Label class="text-xs text-muted-foreground">Erstellt am</Label>
                                    <p class="text-sm">{{ formatDate(caseFile.created_at) }}</p>
                                </div>
                                <div>
                                    <Label class="text-xs text-muted-foreground">Letzte Aktualisierung</Label>
                                    <p class="text-sm">{{ formatDate(caseFile.updated_at) }}</p>
                                </div>
                                <div v-if="caseReference">
                                    <Label class="text-xs text-muted-foreground">Datenbank-Name</Label>
                                    <p class="text-sm font-mono">{{ caseReference.database_name || 'Nicht verfügbar' }}</p>
                                </div>
                                <div v-if="caseReference">
                                    <Label class="text-xs text-muted-foreground">Verbindung aktiv</Label>
                                    <Badge :variant="caseReference.is_active ? 'default' : 'destructive'">
                                        {{ caseReference.is_active ? 'Ja' : 'Nein' }}
                                    </Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { ArrowLeft, Edit, FileText, Trash2 } from 'lucide-vue-next'
import { index, edit, destroy } from '@/routes/cases'

const props = defineProps({
    caseFile: Object,
    caseReference: Object,
})

const formatStatus = (status) => {
    const germanStatus = {
        'draft': 'Entwurf',
        'active': 'Aktiv',
        'initiated': 'Eingeleitet',
        'pending': 'Wartend',
        'hearing_scheduled': 'Anhörung geplant',
        'under_deliberation': 'In Beratung',
        'suspended': 'Ausgesetzt',
        'settled': 'Verglichen',
        'decided': 'Entschieden',
        'closed': 'Geschlossen',
    }
    return germanStatus[status] || status.replace(/_/g, ' ').replace(/\b\w/g, (l) => l.toUpperCase())
}

const getStatusVariant = (status) => {
    const variants = {
        draft: 'secondary',
        active: 'default',
        initiated: 'default',
        pending: 'secondary',
        hearing_scheduled: 'secondary',
        under_deliberation: 'secondary',
        suspended: 'destructive',
        settled: 'default',
        decided: 'default',
        closed: 'secondary',
    }
    return variants[status] || 'secondary'
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

const formatCurrency = (value, currency = 'EUR') => {
    if (!value) return '-'
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: currency
    }).format(value)
}


const deleteCase = () => {
    if (confirm('Sind Sie sicher, dass Sie diesen Fall löschen möchten? Dies wird auch die zugehörige Datenbank löschen.')) {
        router.delete(destroy.url({ case: props.caseFile.id }))
    }
}
</script>