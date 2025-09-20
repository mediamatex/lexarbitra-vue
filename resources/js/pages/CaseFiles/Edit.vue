<template>
    <Head title="Fall bearbeiten" />

    <AppLayout>
        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Fall bearbeiten</h2>
                        <p class="text-muted-foreground">
                            Grundlegende Informationen der Falldatei bearbeiten
                        </p>
                    </div>
                    <Button variant="outline" as-child>
                        <Link :href="show.url({ case: caseReference.id })">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Zurück zum Fall
                        </Link>
                    </Button>
                </div>

                <!-- Form Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Edit class="h-5 w-5" />
                            Falldaten bearbeiten
                        </CardTitle>
                        <CardDescription>
                            Bearbeiten Sie die grundlegenden Informationen der Falldatei.
                            Detaillierte Fallinhalt werden in der dedizierten Falldatenbank gespeichert.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submitForm" class="space-y-6">
                            <!-- Case Number -->
                            <div class="space-y-2">
                                <Label for="case_number" class="text-sm font-medium">
                                    Aktenzeichen
                                    <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="case_number"
                                    v-model="form.case_number"
                                    placeholder="ARB-2024-001"
                                    required
                                />
                                <p v-if="form.errors.case_number" class="text-sm text-red-600">
                                    {{ form.errors.case_number }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Eindeutige Kennzeichnung des Schiedsverfahrens
                                </p>
                            </div>

                            <!-- Title -->
                            <div class="space-y-2">
                                <Label for="title" class="text-sm font-medium">
                                    Titel
                                    <span class="text-red-500">*</span>
                                </Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    placeholder="Streit zwischen Firma A und Firma B"
                                    required
                                />
                                <p v-if="form.errors.title" class="text-sm text-red-600">
                                    {{ form.errors.title }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Kurze prägnante Beschreibung des Falls
                                </p>
                            </div>

                            <!-- Status -->
                            <div class="space-y-2">
                                <Label for="status" class="text-sm font-medium">
                                    Status
                                </Label>
                                <Select v-model="form.status">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Status auswählen" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Entwurf</SelectItem>
                                        <SelectItem value="active">Aktiv</SelectItem>
                                        <SelectItem value="initiated">Eingeleitet</SelectItem>
                                        <SelectItem value="pending">Wartend</SelectItem>
                                        <SelectItem value="hearing_scheduled">Anhörung geplant</SelectItem>
                                        <SelectItem value="under_deliberation">In Beratung</SelectItem>
                                        <SelectItem value="suspended">Ausgesetzt</SelectItem>
                                        <SelectItem value="settled">Verglichen</SelectItem>
                                        <SelectItem value="decided">Entschieden</SelectItem>
                                        <SelectItem value="closed">Geschlossen</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.status" class="text-sm text-red-600">
                                    {{ form.errors.status }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Aktueller Status des Schiedsverfahrens
                                </p>
                            </div>

                            <!-- Database Info (Read-only) -->
                            <div class="space-y-4 rounded-lg border bg-muted/50 p-4">
                                <h4 class="text-sm font-medium text-muted-foreground">Datenbank-Informationen</h4>
                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    <div>
                                        <Label class="text-xs text-muted-foreground">Datenbank Name</Label>
                                        <p class="text-sm font-mono">{{ caseReference.database_name || 'Nicht verfügbar' }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-xs text-muted-foreground">Verbindungsname</Label>
                                        <p class="text-sm font-mono">{{ caseReference.connection_name || 'Nicht verfügbar' }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-xs text-muted-foreground">Erstellt am</Label>
                                        <p class="text-sm">{{ formatDate(caseReference.created_at) }}</p>
                                    </div>
                                    <div>
                                        <Label class="text-xs text-muted-foreground">Tenant Case ID</Label>
                                        <p class="text-sm font-mono">{{ caseReference.tenant_case_id || 'Nicht gesetzt' }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Success Message -->
                            <div v-if="form.recentlySuccessful" class="rounded-md bg-green-50 p-4">
                                <div class="flex">
                                    <Check class="h-5 w-5 text-green-400" />
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-800">
                                            Fall erfolgreich aktualisiert!
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-between pt-6">
                                <Button variant="outline" as-child>
                                    <Link :href="show.url({ case: caseReference.id })">
                                        Abbrechen
                                    </Link>
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    <Save v-if="!form.processing" class="mr-2 h-4 w-4" />
                                    <div v-else class="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
                                    {{ form.processing ? 'Speichern...' : 'Änderungen speichern' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
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
import { ArrowLeft, Edit, Save, Check } from 'lucide-vue-next'
import { show, update } from '@/routes/cases'

const props = defineProps({
    caseFile: Object,
    caseReference: Object,
})

const form = useForm({
    case_number: props.caseFile?.case_number || '',
    title: props.caseFile?.title || '',
    status: props.caseFile?.status || 'active',
})

const submitForm = () => {
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
</script>