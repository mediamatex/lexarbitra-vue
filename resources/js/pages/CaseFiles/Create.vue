<template>
    <Head title="Fall erstellen" />

    <AppLayout>
        <div class="py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Neue Falldatei erstellen</h2>
                        <p class="text-muted-foreground">
                            Schiedsverfahren-Assistent: Eine dedizierte Datenbank wird automatisch erstellt.
                        </p>
                    </div>
                    <Button variant="outline" as-child>
                        <Link :href="index.url()">
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Zurück zu den Fällen
                        </Link>
                    </Button>
                </div>

                <!-- Progress Steps -->
                <Card class="mb-8">
                    <CardContent class="pt-6">
                        <div class="flex items-center justify-between">
                            <div v-for="(step, index) in steps" :key="index" class="flex items-center">
                                <div :class="[
                                    'flex h-8 w-8 items-center justify-center rounded-full border-2 text-sm font-medium',
                                    currentStep === index + 1 ? 'border-primary bg-primary text-primary-foreground' :
                                    currentStep > index + 1 ? 'border-primary bg-primary text-primary-foreground' :
                                    'border-gray-300 bg-white text-gray-500'
                                ]">
                                    <Check v-if="currentStep > index + 1" class="h-4 w-4" />
                                    <span v-else>{{ index + 1 }}</span>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-900">{{ step.title }}</p>
                                    <p class="text-sm text-gray-500">{{ step.description }}</p>
                                </div>
                                <div v-if="index < steps.length - 1" class="mx-8 h-0.5 w-20 bg-gray-200"></div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Form Card -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <component :is="currentStepData.icon" class="h-5 w-5" />
                            {{ currentStepData.title }}
                        </CardTitle>
                        <CardDescription>
                            {{ currentStepData.description }}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form @submit.prevent="handleSubmit" class="space-y-6">
                            <!-- Step 1: Grundlegende Informationen -->
                            <div v-if="currentStep === 1" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Case Number -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">
                                            Aktenzeichen
                                            <span class="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            v-model="form.case_number"
                                            placeholder="ARB-2024-001"
                                            :class="{ 'border-red-500': form.errors.case_number }"
                                        />
                                        <p class="text-xs text-muted-foreground mt-1">Eindeutige Bezeichnung für das Verfahren</p>
                                        <p v-if="form.errors.case_number" class="text-sm text-red-600">{{ form.errors.case_number }}</p>
                                    </div>

                                    <!-- Title -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">
                                            Verfahrenstitel
                                            <span class="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            v-model="form.title"
                                            placeholder="Firma A gegen Firma B - Bauvertragsstreit"
                                            :class="{ 'border-red-500': form.errors.title }"
                                        />
                                        <p v-if="form.errors.title" class="text-sm text-red-600">{{ form.errors.title }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Status -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">
                                            Verfahrensstatus
                                            <span class="text-red-500">*</span>
                                        </Label>
                                        <select
                                            v-model="form.status"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                            :class="{ 'border-red-500': form.errors.status }"
                                        >
                                            <option value="">Status wählen</option>
                                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.status" class="text-sm text-red-600">{{ form.errors.status }}</p>
                                    </div>

                                    <!-- Procedure Type -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">
                                            Verfahrenstyp
                                            <span class="text-red-500">*</span>
                                        </Label>
                                        <select
                                            v-model="form.procedure_type"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                            :class="{ 'border-red-500': form.errors.procedure_type }"
                                        >
                                            <option value="">Verfahrenstyp wählen</option>
                                            <option v-for="option in procedureTypeOptions" :key="option.value" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </select>
                                        <p v-if="form.errors.procedure_type" class="text-sm text-red-600">{{ form.errors.procedure_type }}</p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <!-- Initiated Date -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">
                                            Eingeleitet am
                                            <span class="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            v-model="form.initiated_at"
                                            type="date"
                                            :class="{ 'border-red-500': form.errors.initiated_at }"
                                        />
                                        <p v-if="form.errors.initiated_at" class="text-sm text-red-600">{{ form.errors.initiated_at }}</p>
                                    </div>

                                    <!-- Decision Deadline -->
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">Entscheidungsfrist</Label>
                                        <Input
                                            v-model="form.deadline_decision"
                                            type="date"
                                            :class="{ 'border-red-500': form.errors.deadline_decision }"
                                        />
                                        <p class="text-xs text-muted-foreground mt-1">Optional: Spätester Termin für Entscheidung</p>
                                        <p v-if="form.errors.deadline_decision" class="text-sm text-red-600">{{ form.errors.deadline_decision }}</p>
                                    </div>
                                </div>

                                <!-- Description -->
                                <div class="space-y-2">
                                    <Label class="text-sm font-medium">Verfahrensbeschreibung</Label>
                                    <textarea
                                        v-model="form.description"
                                        rows="4"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                        placeholder="Kurze Beschreibung des Streitgegenstands und der Umstände..."
                                        :class="{ 'border-red-500': form.errors.description }"
                                    ></textarea>
                                    <p v-if="form.errors.description" class="text-sm text-red-600">{{ form.errors.description }}</p>
                                </div>
                            </div>

                            <!-- Step 2: Parteien & Vertreter -->
                            <div v-if="currentStep === 2" class="space-y-8">
                                <!-- Claimant Section -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold flex items-center gap-2">
                                        <Building class="h-5 w-5 text-blue-600" />
                                        Kläger
                                    </h3>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">
                                                Firmenname
                                                <span class="text-red-500">*</span>
                                            </Label>
                                            <Input v-model="form.claimant_company_name" placeholder="Musterfirma GmbH" />
                                            <p v-if="form.errors.claimant_company_name" class="text-sm text-red-600">{{ form.errors.claimant_company_name }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Handelsregisternummer</Label>
                                            <Input v-model="form.claimant_company_registration_number" placeholder="HRB 12345" />
                                            <p v-if="form.errors.claimant_company_registration_number" class="text-sm text-red-600">{{ form.errors.claimant_company_registration_number }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">E-Mail</Label>
                                            <Input v-model="form.claimant_company_email" type="email" placeholder="kontakt@musterfirma.de" />
                                            <p v-if="form.errors.claimant_company_email" class="text-sm text-red-600">{{ form.errors.claimant_company_email }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Telefon</Label>
                                            <Input v-model="form.claimant_company_phone" placeholder="+49 123 456789" />
                                            <p v-if="form.errors.claimant_company_phone" class="text-sm text-red-600">{{ form.errors.claimant_company_phone }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">Adresse</Label>
                                        <textarea
                                            v-model="form.claimant_company_address"
                                            rows="3"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            placeholder="Musterstraße 123&#10;12345 Musterstadt&#10;Deutschland"
                                        ></textarea>
                                        <p v-if="form.errors.claimant_company_address" class="text-sm text-red-600">{{ form.errors.claimant_company_address }}</p>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Respondent Section -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold flex items-center gap-2">
                                        <Building class="h-5 w-5 text-red-600" />
                                        Beklagte
                                    </h3>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">
                                                Firmenname
                                                <span class="text-red-500">*</span>
                                            </Label>
                                            <Input v-model="form.respondent_company_name" placeholder="Beispiel AG" />
                                            <p v-if="form.errors.respondent_company_name" class="text-sm text-red-600">{{ form.errors.respondent_company_name }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Handelsregisternummer</Label>
                                            <Input v-model="form.respondent_company_registration_number" placeholder="HRB 67890" />
                                            <p v-if="form.errors.respondent_company_registration_number" class="text-sm text-red-600">{{ form.errors.respondent_company_registration_number }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">E-Mail</Label>
                                            <Input v-model="form.respondent_company_email" type="email" placeholder="kontakt@beispiel.de" />
                                            <p v-if="form.errors.respondent_company_email" class="text-sm text-red-600">{{ form.errors.respondent_company_email }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Telefon</Label>
                                            <Input v-model="form.respondent_company_phone" placeholder="+49 987 654321" />
                                            <p v-if="form.errors.respondent_company_phone" class="text-sm text-red-600">{{ form.errors.respondent_company_phone }}</p>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <Label class="text-sm font-medium">Adresse</Label>
                                        <textarea
                                            v-model="form.respondent_company_address"
                                            rows="3"
                                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                            placeholder="Beispielweg 456&#10;67890 Beispielstadt&#10;Deutschland"
                                        ></textarea>
                                        <p v-if="form.errors.respondent_company_address" class="text-sm text-red-600">{{ form.errors.respondent_company_address }}</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 3: Verfahrensdetails -->
                            <div v-if="currentStep === 3" class="space-y-6">
                                <!-- Legal Framework -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold flex items-center gap-2">
                                        <Scale class="h-5 w-5 text-green-600" />
                                        Rechtlicher Rahmen
                                    </h3>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Gerichtsstand/Jurisdiktion</Label>
                                            <Input v-model="form.jurisdiction" placeholder="Deutschland" />
                                            <p class="text-xs text-muted-foreground mt-1">z.B. Deutschland, Schweiz</p>
                                            <p v-if="form.errors.jurisdiction" class="text-sm text-red-600">{{ form.errors.jurisdiction }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Anwendbares Recht</Label>
                                            <Input v-model="form.applicable_law" placeholder="Deutsches Recht" />
                                            <p class="text-xs text-muted-foreground mt-1">z.B. Deutsches Recht, Schweizer Recht</p>
                                            <p v-if="form.errors.applicable_law" class="text-sm text-red-600">{{ form.errors.applicable_law }}</p>
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Schiedsinstitution</Label>
                                            <Input v-model="form.arbitration_institution" placeholder="DIS" />
                                            <p class="text-xs text-muted-foreground mt-1">z.B. DIS, ICC, ad hoc</p>
                                            <p v-if="form.errors.arbitration_institution" class="text-sm text-red-600">{{ form.errors.arbitration_institution }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Version der Schiedsordnung</Label>
                                            <Input v-model="form.arbitration_rules_version" placeholder="2018" />
                                            <p class="text-xs text-muted-foreground mt-1">z.B. 2018, 2021</p>
                                            <p v-if="form.errors.arbitration_rules_version" class="text-sm text-red-600">{{ form.errors.arbitration_rules_version }}</p>
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Case Classification -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold flex items-center gap-2">
                                        <Tag class="h-5 w-5 text-purple-600" />
                                        Fallklassifizierung
                                    </h3>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Fallkategorie</Label>
                                            <select
                                                v-model="form.case_category"
                                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                            >
                                                <option value="">Kategorie wählen</option>
                                                <option v-for="option in caseCategoryOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.case_category" class="text-sm text-red-600">{{ form.errors.case_category }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Komplexitätsgrad</Label>
                                            <select
                                                v-model="form.complexity_level"
                                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                            >
                                                <option value="">Komplexität wählen</option>
                                                <option v-for="option in complexityOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.complexity_level" class="text-sm text-red-600">{{ form.errors.complexity_level }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Dringlichkeitsgrad</Label>
                                            <select
                                                v-model="form.urgency_level"
                                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                            >
                                                <option value="">Dringlichkeit wählen</option>
                                                <option v-for="option in urgencyOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.urgency_level" class="text-sm text-red-600">{{ form.errors.urgency_level }}</p>
                                        </div>
                                    </div>
                                </div>

                                <Separator />

                                <!-- Financial Data -->
                                <div class="space-y-4">
                                    <h3 class="text-lg font-semibold flex items-center gap-2">
                                        <Euro class="h-5 w-5 text-yellow-600" />
                                        Finanzielle Angaben
                                    </h3>
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Streitwert</Label>
                                            <Input
                                                v-model="form.dispute_value"
                                                type="number"
                                                step="0.01"
                                                placeholder="100000.00"
                                            />
                                            <p v-if="form.errors.dispute_value" class="text-sm text-red-600">{{ form.errors.dispute_value }}</p>
                                        </div>
                                        <div class="space-y-2">
                                            <Label class="text-sm font-medium">Währung</Label>
                                            <select
                                                v-model="form.currency"
                                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                                            >
                                                <option v-for="option in currencyOptions" :key="option.value" :value="option.value">
                                                    {{ option.label }}
                                                </option>
                                            </select>
                                            <p v-if="form.errors.currency" class="text-sm text-red-600">{{ form.errors.currency }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Step 4: Überprüfung & Erstellung -->
                            <div v-if="currentStep === 4" class="space-y-6">
                                <div class="rounded-lg bg-muted p-6">
                                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                        <FileCheck class="h-5 w-5 text-blue-600" />
                                        Zusammenfassung
                                    </h3>

                                    <!-- Review Summary -->
                                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                        <div class="space-y-3">
                                            <h4 class="font-medium">Grundlegende Informationen</h4>
                                            <div class="space-y-1 text-sm text-muted-foreground">
                                                <p><strong>Aktenzeichen:</strong> {{ form.case_number || 'Nicht angegeben' }}</p>
                                                <p><strong>Titel:</strong> {{ form.title || 'Nicht angegeben' }}</p>
                                                <p><strong>Status:</strong> {{ getOptionLabel(statusOptions, form.status) || 'Nicht angegeben' }}</p>
                                                <p><strong>Verfahrenstyp:</strong> {{ getOptionLabel(procedureTypeOptions, form.procedure_type) || 'Nicht angegeben' }}</p>
                                                <p><strong>Eingeleitet am:</strong> {{ form.initiated_at || 'Nicht angegeben' }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <h4 class="font-medium">Parteien</h4>
                                            <div class="space-y-1 text-sm text-muted-foreground">
                                                <p><strong>Kläger:</strong> {{ form.claimant_company_name || 'Nicht angegeben' }}</p>
                                                <p><strong>Beklagte:</strong> {{ form.respondent_company_name || 'Nicht angegeben' }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <h4 class="font-medium">Verfahrensdetails</h4>
                                            <div class="space-y-1 text-sm text-muted-foreground">
                                                <p><strong>Jurisdiktion:</strong> {{ form.jurisdiction || 'Nicht angegeben' }}</p>
                                                <p><strong>Anwendbares Recht:</strong> {{ form.applicable_law || 'Nicht angegeben' }}</p>
                                                <p><strong>Fallkategorie:</strong> {{ getOptionLabel(caseCategoryOptions, form.case_category) || 'Nicht angegeben' }}</p>
                                                <p><strong>Komplexität:</strong> {{ getOptionLabel(complexityOptions, form.complexity_level) || 'Nicht angegeben' }}</p>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <h4 class="font-medium">Finanzielle Angaben</h4>
                                            <div class="space-y-1 text-sm text-muted-foreground">
                                                <p><strong>Streitwert:</strong> {{ form.dispute_value ? `${form.dispute_value} ${form.currency}` : 'Nicht angegeben' }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div class="flex items-center space-x-2">
                                        <input
                                            id="confirm_data"
                                            v-model="form.confirm_data"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                        />
                                        <Label for="confirm_data" class="text-sm">
                                            Ich bestätige, dass alle eingegebenen Daten korrekt sind und das Verfahren erstellt werden kann. *
                                        </Label>
                                    </div>
                                    <p v-if="form.errors.confirm_data" class="text-sm text-red-600">
                                        {{ form.errors.confirm_data }}
                                    </p>
                                </div>
                            </div>

                            <!-- Navigation Buttons -->
                            <div class="flex justify-between pt-6 border-t">
                                <Button
                                    v-if="currentStep > 1"
                                    type="button"
                                    variant="outline"
                                    @click="previousStep"
                                >
                                    <ChevronLeft class="mr-2 h-4 w-4" />
                                    Zurück
                                </Button>
                                <div v-else></div>

                                <div class="flex gap-4">
                                    <Button type="button" variant="outline" as-child>
                                        <Link :href="index.url()">
                                            Abbrechen
                                        </Link>
                                    </Button>

                                    <Button
                                        v-if="currentStep < 4"
                                        type="button"
                                        @click="nextStep"
                                    >
                                        Weiter
                                        <ChevronRight class="ml-2 h-4 w-4" />
                                    </Button>

                                    <Button
                                        v-else
                                        type="submit"
                                        :disabled="form.processing || !form.confirm_data"
                                        class="bg-green-600 hover:bg-green-700"
                                    >
                                        <Database class="mr-2 h-4 w-4" />
                                        {{ form.processing ? 'Erstelle Fall & Datenbank...' : 'Fall erstellen' }}
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Separator } from '@/components/ui/separator'
import {
    ArrowLeft,
    Database,
    ClipboardList,
    Users,
    Settings,
    FileCheck,
    ChevronLeft,
    ChevronRight,
    Check,
    Building,
    Scale,
    Tag,
    Euro
} from 'lucide-vue-next'
import { index, store } from '@/routes/cases'

// Current step tracker
const currentStep = ref(1)

// Step definitions - making it reactive
const steps = ref([
    {
        title: 'Grundlegende Informationen',
        description: 'Aktenzeichen, Titel und Verfahrensdetails',
        icon: ClipboardList
    },
    {
        title: 'Parteien & Vertreter',
        description: 'Kläger und Beklagte Informationen',
        icon: Users
    },
    {
        title: 'Verfahrensdetails',
        description: 'Rechtlicher Rahmen und Klassifizierung',
        icon: Settings
    },
    {
        title: 'Überprüfung & Erstellung',
        description: 'Daten prüfen und Fall erstellen',
        icon: FileCheck
    }
])

// Computed property for current step data
const currentStepData = computed(() => {
    const index = currentStep.value - 1
    if (index >= 0 && index < steps.value.length) {
        return steps.value[index]
    }
    return steps.value[0] // Fallback to first step
})

// Form data
const form = useForm({
    // Step 1: Basic Information
    case_number: '',
    title: '',
    status: 'draft',
    procedure_type: '',
    initiated_at: new Date().toISOString().split('T')[0],
    deadline_decision: '',
    description: '',

    // Step 2: Parties
    claimant_company_name: '',
    claimant_company_registration_number: '',
    claimant_company_address: '',
    claimant_company_email: '',
    claimant_company_phone: '',

    respondent_company_name: '',
    respondent_company_registration_number: '',
    respondent_company_address: '',
    respondent_company_email: '',
    respondent_company_phone: '',

    // Step 3: Procedure Details
    jurisdiction: '',
    applicable_law: '',
    arbitration_institution: '',
    arbitration_rules_version: '',
    case_category: '',
    complexity_level: '',
    urgency_level: '',
    dispute_value: null,
    currency: 'EUR',

    // Step 4: Confirmation
    confirm_data: false
})

// Options for dropdowns
const statusOptions = [
    { value: 'draft', label: 'Entwurf' },
    { value: 'initiated', label: 'Eingeleitet' },
    { value: 'pending', label: 'Anhängig' },
    { value: 'statement_of_claim', label: 'Klageschrift eingegangen' },
    { value: 'statement_of_defense', label: 'Klageerwiderung eingegangen' },
    { value: 'evidence_exchange', label: 'Beweisaustausch' },
    { value: 'hearing_scheduled', label: 'Anhörung terminiert' },
    { value: 'under_deliberation', label: 'In Beratung' },
    { value: 'decided', label: 'Entschieden' },
    { value: 'closed', label: 'Geschlossen' },
    { value: 'suspended', label: 'Ausgesetzt' },
    { value: 'settled', label: 'Vergleich' }
]

const procedureTypeOptions = [
    { value: 'main_procedure', label: 'Hauptverfahren' },
    { value: 'sub_procedure', label: 'Unterverfahren' },
    { value: 'expert_procedure', label: 'Sachverständigenverfahren' },
    { value: 'expedited_procedure', label: 'Eilverfahren' },
    { value: 'mediation', label: 'Mediationsverfahren' }
]

const caseCategoryOptions = [
    { value: 'construction', label: 'Bau und Architektur' },
    { value: 'commercial', label: 'Handels- und Gesellschaftsrecht' },
    { value: 'employment', label: 'Arbeitsrecht' },
    { value: 'intellectual_property', label: 'Geistiges Eigentum' },
    { value: 'insurance', label: 'Versicherungsrecht' },
    { value: 'banking', label: 'Bank- und Finanzrecht' },
    { value: 'energy', label: 'Energierecht' },
    { value: 'sports', label: 'Sportrecht' },
    { value: 'technology', label: 'IT und Technologie' },
    { value: 'international_trade', label: 'Internationaler Handel' },
    { value: 'other', label: 'Sonstiges' }
]

const complexityOptions = [
    { value: 'simple', label: 'Einfach' },
    { value: 'medium', label: 'Mittel' },
    { value: 'high', label: 'Hoch' },
    { value: 'very_high', label: 'Sehr hoch' }
]

const urgencyOptions = [
    { value: 'normal', label: 'Normal' },
    { value: 'urgent', label: 'Dringend' },
    { value: 'very_urgent', label: 'Sehr dringend' },
    { value: 'critical', label: 'Kritisch' }
]

const currencyOptions = [
    { value: 'EUR', label: 'Euro (€)' },
    { value: 'USD', label: 'US-Dollar ($)' },
    { value: 'CHF', label: 'Schweizer Franken (CHF)' },
    { value: 'GBP', label: 'Britisches Pfund (£)' }
]

// Navigation functions
const nextStep = () => {
    if (currentStep.value < 4) {
        currentStep.value++
    }
}

const previousStep = () => {
    if (currentStep.value > 1) {
        currentStep.value--
    }
}

// Helper function to get option label
const getOptionLabel = (options, value) => {
    const option = options.find(opt => opt.value === value)
    return option ? option.label : ''
}

// Form submission
const handleSubmit = () => {
    if (currentStep.value < 4) {
        nextStep()
    } else {
        submit()
    }
}

const submit = () => {
    form.post(store.url(), {
        preserveScroll: true,
    })
}
</script>