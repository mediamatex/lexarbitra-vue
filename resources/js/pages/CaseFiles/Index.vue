<template>
    <Head title="Fälle" />

    <AppLayout>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8 flex items-center justify-between">
                    <div>
                        <h2 class="text-3xl font-bold tracking-tight">Fälle</h2>
                        <p class="text-muted-foreground">
                            Verwalten Sie Ihre Schiedsverfahren und deren dedizierte Datenbanken
                        </p>
                    </div>
                    <Button as-child>
                        <Link :href="create.url()">
                            <Plus class="mr-2 h-4 w-4" />
                            Neuen Fall erstellen
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardContent class="p-6">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Aktenzeichen</TableHead>
                                    <TableHead>Titel</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Initiiert</TableHead>
                                    <TableHead class="text-right">Aktionen</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow
                                    v-for="caseFile in cases.data"
                                    :key="caseFile.id"
                                >
                                    <TableCell class="font-medium">
                                        {{ caseFile.case_number }}
                                    </TableCell>
                                    <TableCell>
                                        {{ caseFile.title }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge :variant="getStatusVariant(caseFile.status)">
                                            {{ formatStatus(caseFile.status) }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>
                                        {{ formatDate(caseFile.initiated_at) }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div class="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                as-child
                                            >
                                                <Link :href="show.url({ case: caseFile.id })">
                                                    <Eye class="mr-1 h-3 w-3" />
                                                    Ansehen
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                as-child
                                            >
                                                <Link :href="edit.url({ case: caseFile.id })">
                                                    <Edit class="mr-1 h-3 w-3" />
                                                    Bearbeiten
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                @click="deleteCase(caseFile.id)"
                                            >
                                                <Trash2 class="mr-1 h-3 w-3" />
                                                Löschen
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <!-- Pagination -->
                        <div v-if="cases.links" class="mt-6 flex items-center justify-between">
                            <div class="text-sm text-muted-foreground">
                                Zeige {{ cases.from }} bis {{ cases.to }} von {{ cases.total }} Ergebnissen
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    v-if="cases.prev_page_url"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                >
                                    <a :href="cases.prev_page_url">
                                        <ChevronLeft class="mr-1 h-3 w-3" />
                                        Vorherige
                                    </a>
                                </Button>
                                <Button
                                    v-if="cases.next_page_url"
                                    variant="outline"
                                    size="sm"
                                    as-child
                                >
                                    <a :href="cases.next_page_url">
                                        Nächste
                                        <ChevronRight class="ml-1 h-3 w-3" />
                                    </a>
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>

<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Badge } from '@/components/ui/badge'
import { Plus, Eye, Edit, Trash2, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { create, show, edit, destroy } from '@/routes/cases'

defineProps({
    cases: Object,
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
    return new Date(dateString).toLocaleDateString()
}

const deleteCase = (caseId) => {
    if (confirm('Are you sure you want to delete this case? This will also delete the associated database.')) {
        router.delete(destroy.url({ case: caseId }))
    }
}
</script>