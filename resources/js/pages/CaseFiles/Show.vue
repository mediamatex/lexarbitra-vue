<template>
    <Head :title="`Fall ${caseFile.case_number}`" />

    <AppLayout>
        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <h2 class="text-3xl font-bold tracking-tight">{{ caseFile.case_number }}</h2>
                            <Badge :variant="getStatusVariant(caseFile.status)" class="text-sm">
                                {{ formatStatus(caseFile.status) }}
                            </Badge>
                        </div>
                        <p class="text-muted-foreground">
                            {{ caseFile.title }}
                        </p>
                        <div v-if="currentUserRole" class="flex items-center gap-2 mt-2">
                            <Badge variant="outline" class="text-xs">
                                <Shield class="mr-1 h-3 w-3" />
                                Ihre Rolle: {{ getRoleDisplayName(currentUserRole) }}
                            </Badge>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <Button variant="outline" as-child>
                            <Link :href="index.url()">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Zurück zu den Fällen
                            </Link>
                        </Button>
                        <Button
                            v-if="currentUserPermissions.includes('manage_case') || currentUserPermissions.includes('*')"
                            variant="outline"
                            as-child
                        >
                            <Link :href="edit.url({ case: caseFile.id })">
                                <Edit class="mr-2 h-4 w-4" />
                                Bearbeiten
                            </Link>
                        </Button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8 lg:grid-cols-3">
                    <!-- Main Content -->
                    <div class="lg:col-span-2 space-y-8">
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

                        <!-- Participants Section -->
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Users class="h-5 w-5" />
                                        Verfahrensbeteiligte
                                    </CardTitle>
                                    <CardDescription>
                                        Alle am Verfahren beteiligten Personen und ihre Rollen
                                    </CardDescription>
                                </div>
                                <Button
                                    v-if="canManageParticipants"
                                    variant="outline"
                                    size="sm"
                                    @click="showAddParticipant = true"
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Teilnehmer hinzufügen
                                </Button>
                            </CardHeader>
                            <CardContent>
                                <div v-if="participants.length === 0" class="text-center py-8 text-muted-foreground">
                                    <Users class="h-12 w-12 mx-auto mb-4 text-muted-foreground/50" />
                                    <p>Noch keine Teilnehmer zugewiesen</p>
                                </div>

                                <div v-else class="space-y-4">
                                    <!-- Group participants by role type -->
                                    <div v-for="(group, groupName) in groupedParticipants" :key="groupName">
                                        <h4 class="text-sm font-semibold text-muted-foreground mb-3 flex items-center gap-2">
                                            <component :is="getGroupIcon(groupName)" class="h-4 w-4" />
                                            {{ getGroupName(groupName) }}
                                        </h4>

                                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                            <Card v-for="participant in group" :key="participant.id" class="border border-border/50">
                                                <CardContent class="p-4">
                                                    <div class="flex items-start gap-3">
                                                        <Avatar class="h-10 w-10">
                                                            <AvatarImage
                                                                v-if="participant.user.avatar_url"
                                                                :src="participant.user.avatar_url"
                                                                :alt="participant.user.name"
                                                            />
                                                            <AvatarFallback>
                                                                {{ getInitials(participant.user.name) }}
                                                            </AvatarFallback>
                                                        </Avatar>

                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-1">
                                                                <h5 class="text-sm font-semibold truncate">
                                                                    {{ participant.user.name }}
                                                                </h5>
                                                                <Badge
                                                                    :variant="getRoleVariant(participant.role)"
                                                                    class="text-xs"
                                                                >
                                                                    {{ participant.role_display }}
                                                                </Badge>
                                                                <Badge
                                                                    v-if="participant.is_primary"
                                                                    variant="default"
                                                                    class="text-xs"
                                                                >
                                                                    <Crown class="mr-1 h-3 w-3" />
                                                                    Primär
                                                                </Badge>
                                                            </div>

                                                            <p class="text-xs text-muted-foreground mb-2">
                                                                {{ participant.user.email }}
                                                            </p>

                                                            <div v-if="participant.user.title || participant.user.law_firm" class="text-xs text-muted-foreground">
                                                                <p v-if="participant.user.title">{{ participant.user.title }}</p>
                                                                <p v-if="participant.user.law_firm">{{ participant.user.law_firm }}</p>
                                                            </div>

                                                            <div class="mt-3">
                                                                <Tooltip>
                                                                    <TooltipTrigger>
                                                                        <div class="text-xs text-muted-foreground">
                                                                            Berechtigt: {{ participant.permissions.length }} Aktionen
                                                                        </div>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent>
                                                                        <div class="text-xs space-y-1">
                                                                            <p class="font-semibold">{{ participant.role_description }}</p>
                                                                            <div class="mt-2">
                                                                                <p class="font-medium mb-1">Berechtigungen:</p>
                                                                                <ul class="space-y-0.5">
                                                                                    <li v-for="permission in participant.permissions.slice(0, 5)" :key="permission">
                                                                                        • {{ formatPermission(permission) }}
                                                                                    </li>
                                                                                    <li v-if="participant.permissions.length > 5" class="text-muted-foreground">
                                                                                        ...und {{ participant.permissions.length - 5 }} weitere
                                                                                    </li>
                                                                                </ul>
                                                                            </div>
                                                                        </div>
                                                                    </TooltipContent>
                                                                </Tooltip>
                                                            </div>
                                                        </div>

                                                        <div class="flex flex-col gap-1">
                                                            <Button
                                                                v-if="canManageParticipants"
                                                                variant="ghost"
                                                                size="sm"
                                                                @click="editParticipant(participant)"
                                                            >
                                                                <Edit class="h-3 w-3" />
                                                            </Button>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </div>
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
                                <Button
                                    v-if="currentUserPermissions.includes('manage_case') || currentUserPermissions.includes('*')"
                                    variant="outline"
                                    class="w-full justify-start"
                                    as-child
                                >
                                    <Link :href="edit.url({ case: caseFile.id })">
                                        <Edit class="mr-2 h-4 w-4" />
                                        Fall bearbeiten
                                    </Link>
                                </Button>

                                <Button
                                    v-if="canManageParticipants"
                                    variant="outline"
                                    class="w-full justify-start"
                                    @click="showAddParticipant = true"
                                >
                                    <Plus class="mr-2 h-4 w-4" />
                                    Teilnehmer hinzufügen
                                </Button>

                                <Button
                                    v-if="currentUserPermissions.includes('upload_documents') || currentUserPermissions.includes('*')"
                                    variant="outline"
                                    class="w-full justify-start"
                                >
                                    <Upload class="mr-2 h-4 w-4" />
                                    Dokument hochladen
                                </Button>

                                <Button
                                    v-if="currentUserPermissions.includes('send_messages') || currentUserPermissions.includes('*')"
                                    variant="outline"
                                    class="w-full justify-start"
                                >
                                    <MessageSquare class="mr-2 h-4 w-4" />
                                    Nachricht senden
                                </Button>

                                <Separator />

                                <Button
                                    v-if="currentUserPermissions.includes('*') || currentUserRole === 'referee'"
                                    variant="destructive"
                                    class="w-full justify-start"
                                    @click="deleteCase"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Fall löschen
                                </Button>
                            </CardContent>
                        </Card>

                        <!-- Your Role & Permissions -->
                        <Card v-if="currentUserRole">
                            <CardHeader>
                                <CardTitle class="text-lg flex items-center gap-2">
                                    <Shield class="h-4 w-4" />
                                    Ihre Rolle
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <Badge :variant="getRoleVariant(currentUserRole)" class="text-sm">
                                            {{ getRoleDisplayName(currentUserRole) }}
                                        </Badge>
                                    </div>
                                    <p class="text-xs text-muted-foreground">
                                        {{ getRoleDescription(currentUserRole) }}
                                    </p>
                                </div>

                                <div>
                                    <Label class="text-xs text-muted-foreground">Berechtigungen</Label>
                                    <div class="mt-2 space-y-1">
                                        <div v-for="permission in displayPermissions" :key="permission" class="flex items-center gap-2 text-xs">
                                            <Check class="h-3 w-3 text-green-600" />
                                            {{ formatPermission(permission) }}
                                        </div>
                                        <div v-if="currentUserPermissions.length > 5" class="text-xs text-muted-foreground mt-2">
                                            ...und {{ currentUserPermissions.length - 5 }} weitere Berechtigungen
                                        </div>
                                    </div>
                                </div>
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
                                <div>
                                    <Label class="text-xs text-muted-foreground">Teilnehmer</Label>
                                    <p class="text-sm">{{ participants.length }} Person(en)</p>
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
import { ref, computed } from 'vue'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Label } from '@/components/ui/label'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import {
    ArrowLeft,
    Edit,
    FileText,
    Trash2,
    Users,
    Shield,
    Plus,
    Crown,
    Check,
    Upload,
    MessageSquare,
    Scale,
    Building,
    User,
    Settings
} from 'lucide-vue-next'
import { index, edit, destroy } from '@/routes/cases'

const props = defineProps({
    caseFile: Object,
    caseReference: Object,
    participants: Array,
    currentUserRole: String,
    currentUserPermissions: Array,
    canManageParticipants: Boolean,
})

const showAddParticipant = ref(false)

// Group participants by role type
const groupedParticipants = computed(() => {
    const groups = {
        referees: [],
        parties: [],
        experts: [],
        admin: []
    }

    props.participants.forEach(participant => {
        if (['chairman', 'referee', 'co_referee'].includes(participant.role)) {
            groups.referees.push(participant)
        } else if (['claimant', 'respondent'].includes(participant.role)) {
            groups.parties.push(participant)
        } else if (['expert', 'witness'].includes(participant.role)) {
            groups.experts.push(participant)
        } else {
            groups.admin.push(participant)
        }
    })

    // Remove empty groups
    return Object.fromEntries(
        Object.entries(groups).filter(([key, value]) => value.length > 0)
    )
})

// Display permissions (limit to first 5)
const displayPermissions = computed(() => {
    if (props.currentUserPermissions.includes('*')) {
        return ['Vollzugriff auf alle Funktionen']
    }
    return props.currentUserPermissions.slice(0, 5)
})

// Helper functions
const getInitials = (name) => {
    return name.split(' ').map(word => word.charAt(0)).join('').toUpperCase()
}

const getGroupName = (groupKey) => {
    const names = {
        referees: 'Schiedsrichter',
        parties: 'Verfahrensparteien',
        experts: 'Experten & Zeugen',
        admin: 'Administration'
    }
    return names[groupKey] || groupKey
}

const getGroupIcon = (groupKey) => {
    const icons = {
        referees: Scale,
        parties: Building,
        experts: User,
        admin: Settings
    }
    return icons[groupKey] || User
}

const getRoleVariant = (role) => {
    const variants = {
        chairman: 'default',
        referee: 'default',
        co_referee: 'secondary',
        claimant: 'destructive',
        respondent: 'destructive',
        expert: 'secondary',
        witness: 'outline',
        administrator: 'default',
        lawyer: 'secondary',
        assistant: 'outline'
    }
    return variants[role] || 'outline'
}

const getRoleDisplayName = (role) => {
    const names = {
        chairman: 'Vorsitzender',
        referee: 'Schiedsrichter',
        co_referee: 'Co-Schiedsrichter',
        claimant: 'Kläger',
        respondent: 'Beklagte',
        expert: 'Experte',
        witness: 'Zeuge',
        administrator: 'Administrator',
        lawyer: 'Anwalt',
        assistant: 'Assistent'
    }
    return names[role] || role
}

const getRoleDescription = (role) => {
    const descriptions = {
        chairman: 'Leitet das Schiedsverfahren und trifft endgültige Entscheidungen',
        referee: 'Entscheidet über den Fall und erstellt Schiedssprüche',
        co_referee: 'Unterstützt den Hauptschiedsrichter bei Entscheidungen',
        claimant: 'Initiiert das Schiedsverfahren und stellt Ansprüche',
        respondent: 'Verteidigt sich gegen die Ansprüche des Klägers',
        expert: 'Stellt Expertenwissen in spezifischen Bereichen zur Verfügung',
        witness: 'Gibt Zeugnis über relevante Fakten ab',
        administrator: 'Verwaltet das Verfahren und unterstützt organisatorisch',
        lawyer: 'Vertritt eine Partei rechtlich im Verfahren',
        assistant: 'Unterstützt andere Teilnehmer bei der Verfahrensabwicklung'
    }
    return descriptions[role] || 'Unbekannte Rolle'
}

const formatPermission = (permission) => {
    const permissions = {
        'manage_case': 'Fall verwalten',
        'access_internal_notes': 'Interne Notizen',
        'access_internal_messages': 'Interne Nachrichten',
        'upload_documents': 'Dokumente hochladen',
        'view_all_documents': 'Alle Dokumente einsehen',
        'set_deadlines': 'Fristen setzen',
        'create_calendar_events': 'Termine erstellen',
        'send_messages': 'Nachrichten senden',
        'view_all_messages': 'Alle Nachrichten einsehen',
        'manage_participants': 'Teilnehmer verwalten',
        'create_decisions': 'Entscheidungen erstellen',
        'schedule_hearings': 'Anhörungen planen'
    }
    return permissions[permission] || permission.replace(/_/g, ' ')
}

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

const editParticipant = (participant) => {
    // TODO: Implement participant editing
    console.log('Edit participant:', participant)
}

const deleteCase = () => {
    if (confirm('Sind Sie sicher, dass Sie diesen Fall löschen möchten? Dies wird auch die zugehörige Datenbank löschen.')) {
        router.delete(destroy.url({ case: props.caseFile.id }))
    }
}
</script>