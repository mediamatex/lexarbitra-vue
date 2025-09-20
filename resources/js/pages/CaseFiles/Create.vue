<template>
    <Head title="Fall erstellen" />

    <AppLayout>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Neue Falldatei erstellen</h2>
                        <p class="text-muted-foreground">
                            Erstellen Sie einen neuen Schiedsfall. Eine dedizierte Datenbank wird automatisch erstellt.
                        </p>
                    </div>
                    <Button variant="outline" as-child>
                        <Link :href="index.url()">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Zurück zu den Fällen
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Fall-Informationen</CardTitle>
                        <CardDescription>
                            Füllen Sie die Details unten aus, um Ihre neue Falldatei zu erstellen.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="submit" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <!-- Case Number -->
                                <div class="space-y-2">
                                    <Label for="case_number">Aktenzeichen *</Label>
                                    <Input
                                        id="case_number"
                                        v-model="form.case_number"
                                        type="text"
                                        placeholder="Az. 1/2024"
                                        :class="{ 'border-red-500': form.errors.case_number }"
                                    />
                                    <p v-if="form.errors.case_number" class="text-sm text-red-600">
                                        {{ form.errors.case_number }}
                                    </p>
                                </div>

                                <!-- Initiated Date -->
                                <div class="space-y-2">
                                    <Label for="initiated_at">Einleitungsdatum *</Label>
                                    <Input
                                        id="initiated_at"
                                        v-model="form.initiated_at"
                                        type="date"
                                        :class="{ 'border-red-500': form.errors.initiated_at }"
                                    />
                                    <p v-if="form.errors.initiated_at" class="text-sm text-red-600">
                                        {{ form.errors.initiated_at }}
                                    </p>
                                </div>
                            </div>

                            <!-- Title -->
                            <div class="space-y-2">
                                <Label for="title">Fall-Titel *</Label>
                                <Input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    placeholder="Firma A gegen Firma B - Bauvertragsstreit"
                                    :class="{ 'border-red-500': form.errors.title }"
                                />
                                <p v-if="form.errors.title" class="text-sm text-red-600">
                                    {{ form.errors.title }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label for="description">Beschreibung</Label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Kurze Beschreibung des Falls..."
                                    :class="{ 'border-red-500': form.errors.description }"
                                ></textarea>
                                <p v-if="form.errors.description" class="text-sm text-red-600">
                                    {{ form.errors.description }}
                                </p>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <!-- Dispute Value -->
                                <div class="space-y-2">
                                    <Label for="dispute_value">Dispute Value</Label>
                                    <Input
                                        id="dispute_value"
                                        v-model="form.dispute_value"
                                        type="number"
                                        step="0.01"
                                        placeholder="100000.00"
                                        :class="{ 'border-red-500': form.errors.dispute_value }"
                                    />
                                    <p v-if="form.errors.dispute_value" class="text-sm text-red-600">
                                        {{ form.errors.dispute_value }}
                                    </p>
                                </div>

                                <!-- Currency -->
                                <div class="space-y-2">
                                    <Label for="currency">Currency</Label>
                                    <Input
                                        id="currency"
                                        v-model="form.currency"
                                        type="text"
                                        placeholder="EUR"
                                        maxlength="3"
                                        :class="{ 'border-red-500': form.errors.currency }"
                                    />
                                    <p v-if="form.errors.currency" class="text-sm text-red-600">
                                        {{ form.errors.currency }}
                                    </p>
                                </div>

                                <!-- Jurisdiction -->
                                <div class="space-y-2">
                                    <Label for="jurisdiction">Jurisdiction</Label>
                                    <Input
                                        id="jurisdiction"
                                        v-model="form.jurisdiction"
                                        type="text"
                                        placeholder="Germany"
                                        :class="{ 'border-red-500': form.errors.jurisdiction }"
                                    />
                                    <p v-if="form.errors.jurisdiction" class="text-sm text-red-600">
                                        {{ form.errors.jurisdiction }}
                                    </p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                                <!-- Case Category -->
                                <div class="space-y-2">
                                    <Label for="case_category">Case Category</Label>
                                    <Input
                                        id="case_category"
                                        v-model="form.case_category"
                                        type="text"
                                        placeholder="Construction"
                                        :class="{ 'border-red-500': form.errors.case_category }"
                                    />
                                    <p v-if="form.errors.case_category" class="text-sm text-red-600">
                                        {{ form.errors.case_category }}
                                    </p>
                                </div>

                                <!-- Complexity Level -->
                                <div class="space-y-2">
                                    <Label for="complexity_level">Complexity Level</Label>
                                    <select
                                        id="complexity_level"
                                        v-model="form.complexity_level"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="{ 'border-red-500': form.errors.complexity_level }"
                                    >
                                        <option value="">Select complexity</option>
                                        <option value="low">Low</option>
                                        <option value="medium">Medium</option>
                                        <option value="high">High</option>
                                    </select>
                                    <p v-if="form.errors.complexity_level" class="text-sm text-red-600">
                                        {{ form.errors.complexity_level }}
                                    </p>
                                </div>

                                <!-- Urgency Level -->
                                <div class="space-y-2">
                                    <Label for="urgency_level">Urgency Level</Label>
                                    <select
                                        id="urgency_level"
                                        v-model="form.urgency_level"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
                                        :class="{ 'border-red-500': form.errors.urgency_level }"
                                    >
                                        <option value="">Select urgency</option>
                                        <option value="normal">Normal</option>
                                        <option value="urgent">Urgent</option>
                                        <option value="very_urgent">Very Urgent</option>
                                    </select>
                                    <p v-if="form.errors.urgency_level" class="text-sm text-red-600">
                                        {{ form.errors.urgency_level }}
                                    </p>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex justify-end gap-4">
                                <Button type="button" variant="outline" as-child>
                                    <Link :href="index.url()">
                                        Cancel
                                    </Link>
                                </Button>
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                >
                                    <Database class="mr-2 h-4 w-4" />
                                    {{ form.processing ? 'Creating Case & Database...' : 'Create Case' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { ArrowLeft, Database } from 'lucide-vue-next'
import { index, store } from '@/routes/cases'

const form = useForm({
    case_number: '',
    title: '',
    description: '',
    dispute_value: null,
    currency: 'EUR',
    initiated_at: new Date().toISOString().split('T')[0],
    jurisdiction: '',
    case_category: '',
    complexity_level: '',
    urgency_level: '',
})

const submit = () => {
    form.post(store.url(), {
        preserveScroll: true,
    })
}
</script>