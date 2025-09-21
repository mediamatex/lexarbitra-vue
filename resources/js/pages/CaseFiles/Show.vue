<template>
    <Head :title="`Fall ${caseFile.case_number}`" />

    <AppLayout>
        <div class="min-h-screen bg-gray-50">
            <div class="mx-auto max-w-7xl">
                <!-- Professional Header -->
                <div class="bg-white border-b border-gray-200 px-6 py-8">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-6 mb-4">
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900">{{ caseFile.case_number }}</h1>
                                    <p class="text-lg text-gray-600 mt-1">{{ caseFile.title }}</p>
                                </div>
                                <Badge :variant="getStatusVariant(caseFile.status)" class="text-sm font-medium">
                                    <component :is="getStatusIcon(caseFile.status)" class="w-4 h-4 mr-2" />
                                    {{ formatStatus(caseFile.status) }}
                                </Badge>
                            </div>

                            <div class="flex items-center gap-6 text-sm text-gray-500">
                                <div class="flex items-center gap-2">
                                    <Calendar class="w-4 h-4" />
                                    <span>Erstellt {{ formatRelativeDate(caseFile.created_at) }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <Users class="w-4 h-4" />
                                    <span>{{ participants.length }} Teilnehmer</span>
                                </div>
                                <div v-if="currentUserRole" class="flex items-center gap-2">
                                    <Shield class="w-4 h-4" />
                                    <span>{{ getRoleDisplayName(currentUserRole) }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <Button variant="outline" as-child>
                                <Link :href="index.url()">
                                    <ArrowLeft class="w-4 h-4 mr-2" />
                                    Zurück
                                </Link>
                            </Button>
                            <Button
                                v-if="currentUserPermissions.includes('manage_case') || currentUserPermissions.includes('*')"
                                as-child
                            >
                                <Link :href="edit.url({ case: caseFile.id })">
                                    <Edit class="w-4 h-4 mr-2" />
                                    Bearbeiten
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Professional Tabs -->
                <div class="bg-white border-b border-gray-200">
                    <div class="px-6">
                        <Tabs default-value="overview" class="w-full">
                            <TabsList class="grid w-full grid-cols-5 bg-transparent border-b-0 h-auto p-0">
                                <TabsTrigger
                                    value="overview"
                                    class="flex items-center justify-center gap-2 px-6 py-4 border-b-2 border-transparent data-[state=active]:border-blue-600 data-[state=active]:bg-transparent rounded-none"
                                >
                                    <FileText class="w-4 h-4" />
                                    <span>Übersicht</span>
                                </TabsTrigger>
                                <TabsTrigger
                                    value="participants"
                                    class="flex items-center justify-center gap-2 px-6 py-4 border-b-2 border-transparent data-[state=active]:border-blue-600 data-[state=active]:bg-transparent rounded-none"
                                >
                                    <Users class="w-4 h-4" />
                                    <span>Teilnehmer</span>
                                </TabsTrigger>
                                <TabsTrigger
                                    value="timeline"
                                    class="flex items-center justify-center gap-2 px-6 py-4 border-b-2 border-transparent data-[state=active]:border-blue-600 data-[state=active]:bg-transparent rounded-none"
                                >
                                    <Clock class="w-4 h-4" />
                                    <span>Verlauf</span>
                                </TabsTrigger>
                                <TabsTrigger
                                    value="documents"
                                    class="flex items-center justify-center gap-2 px-6 py-4 border-b-2 border-transparent data-[state=active]:border-blue-600 data-[state=active]:bg-transparent rounded-none"
                                >
                                    <FolderOpen class="w-4 h-4" />
                                    <span>Dokumente</span>
                                </TabsTrigger>
                                <TabsTrigger
                                    value="settings"
                                    class="flex items-center justify-center gap-2 px-6 py-4 border-b-2 border-transparent data-[state=active]:border-blue-600 data-[state=active]:bg-transparent rounded-none"
                                >
                                    <Settings class="w-4 h-4" />
                                    <span>Einstellungen</span>
                                </TabsTrigger>
                            </TabsList>

                            <!-- Overview Tab Content -->
                            <TabsContent value="overview" class="px-6 py-8 space-y-8">
                                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                    <!-- Case Details -->
                                    <div class="lg:col-span-2">
                                        <Card>
                                            <CardHeader>
                                                <CardTitle class="flex items-center gap-2">
                                                    <FileText class="w-5 h-5" />
                                                    Falldaten
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent class="space-y-6">
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                    <div>
                                                        <Label class="text-sm font-medium text-gray-500">Aktenzeichen</Label>
                                                        <p class="text-lg font-semibold text-gray-900 mt-1">{{ caseFile.case_number }}</p>
                                                    </div>

                                                    <div>
                                                        <Label class="text-sm font-medium text-gray-500">Status</Label>
                                                        <div class="flex items-center gap-3 mt-1">
                                                            <Badge :variant="getStatusVariant(caseFile.status)">
                                                                <component :is="getStatusIcon(caseFile.status)" class="w-4 h-4 mr-2" />
                                                                {{ formatStatus(caseFile.status) }}
                                                            </Badge>
                                                            <Progress :value="getStatusProgress(caseFile.status)" class="flex-1" />
                                                        </div>
                                                    </div>

                                                    <div class="md:col-span-2">
                                                        <Label class="text-sm font-medium text-gray-500">Verfahrenstitel</Label>
                                                        <p class="text-base text-gray-900 mt-1">{{ caseFile.title }}</p>
                                                    </div>

                                                    <div>
                                                        <Label class="text-sm font-medium text-gray-500">Initiiert am</Label>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <Calendar class="w-4 h-4 text-gray-400" />
                                                            <p class="text-base text-gray-900">{{ formatDate(caseFile.initiated_at) }}</p>
                                                        </div>
                                                    </div>

                                                    <div v-if="caseFile.dispute_value">
                                                        <Label class="text-sm font-medium text-gray-500">Streitwert</Label>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <Euro class="w-4 h-4 text-gray-400" />
                                                            <p class="text-lg font-semibold text-gray-900">
                                                                {{ formatCurrency(caseFile.dispute_value, caseFile.currency) }}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div v-if="caseFile.description">
                                                    <Label class="text-sm font-medium text-gray-500">Beschreibung</Label>
                                                    <div class="mt-1 p-4 bg-gray-50 rounded-lg border">
                                                        <p class="text-sm text-gray-700 leading-relaxed">{{ caseFile.description }}</p>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t">
                                                    <div v-if="caseFile.jurisdiction">
                                                        <Label class="text-sm font-medium text-gray-500">Zuständigkeit</Label>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <Scale class="w-4 h-4 text-gray-400" />
                                                            <p class="text-sm text-gray-900">{{ caseFile.jurisdiction }}</p>
                                                        </div>
                                                    </div>

                                                    <div v-if="caseFile.case_category">
                                                        <Label class="text-sm font-medium text-gray-500">Kategorie</Label>
                                                        <div class="flex items-center gap-2 mt-1">
                                                            <Tag class="w-4 h-4 text-gray-400" />
                                                            <p class="text-sm text-gray-900">{{ caseFile.case_category }}</p>
                                                        </div>
                                                    </div>

                                                    <div v-if="caseFile.complexity_level">
                                                        <Label class="text-sm font-medium text-gray-500">Komplexität</Label>
                                                        <Badge :variant="getComplexityVariant(caseFile.complexity_level)" class="mt-1">
                                                            {{ formatComplexity(caseFile.complexity_level) }}
                                                        </Badge>
                                                    </div>

                                                    <div v-if="caseFile.urgency_level">
                                                        <Label class="text-sm font-medium text-gray-500">Dringlichkeit</Label>
                                                        <Badge :variant="getUrgencyVariant(caseFile.urgency_level)" class="mt-1">
                                                            {{ formatUrgency(caseFile.urgency_level) }}
                                                        </Badge>
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    <!-- Actions & Stats Sidebar -->
                                    <div class="space-y-6">
                                        <!-- Quick Actions -->
                                        <Card>
                                            <CardHeader>
                                                <CardTitle class="text-lg flex items-center gap-2">
                                                    <Zap class="w-5 h-5" />
                                                    Aktionen
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent class="space-y-3">
                                                <Button
                                                    v-if="currentUserPermissions.includes('manage_case') || currentUserPermissions.includes('*')"
                                                    variant="outline"
                                                    class="w-full justify-start"
                                                    as-child
                                                >
                                                    <Link :href="edit.url({ case: caseFile.id })">
                                                        <Edit class="w-4 h-4 mr-2" />
                                                        Fall bearbeiten
                                                    </Link>
                                                </Button>

                                                <Button
                                                    v-if="canManageParticipants"
                                                    variant="outline"
                                                    class="w-full justify-start"
                                                    @click="showAddParticipant = true"
                                                >
                                                    <Plus class="w-4 h-4 mr-2" />
                                                    Teilnehmer hinzufügen
                                                </Button>

                                                <Button
                                                    v-if="currentUserPermissions.includes('upload_documents') || currentUserPermissions.includes('*')"
                                                    variant="outline"
                                                    class="w-full justify-start"
                                                >
                                                    <Upload class="w-4 h-4 mr-2" />
                                                    Dokument hochladen
                                                </Button>

                                                <Button
                                                    v-if="currentUserPermissions.includes('send_messages') || currentUserPermissions.includes('*')"
                                                    variant="outline"
                                                    class="w-full justify-start"
                                                >
                                                    <MessageSquare class="w-4 h-4 mr-2" />
                                                    Nachricht senden
                                                </Button>

                                                <Separator />

                                                <Button
                                                    v-if="currentUserPermissions.includes('*') || currentUserRole === 'referee'"
                                                    variant="destructive"
                                                    class="w-full justify-start"
                                                    @click="deleteCase"
                                                >
                                                    <Trash2 class="w-4 h-4 mr-2" />
                                                    Fall löschen
                                                </Button>
                                            </CardContent>
                                        </Card>

                                        <!-- Your Role Card -->
                                        <Card v-if="currentUserRole">
                                            <CardHeader>
                                                <CardTitle class="text-lg flex items-center gap-2">
                                                    <Shield class="w-5 h-5" />
                                                    Ihre Rolle
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent class="space-y-4">
                                                <div class="text-center">
                                                    <Badge :variant="getRoleVariant(currentUserRole)" class="text-sm font-medium px-3 py-1">
                                                        {{ getRoleDisplayName(currentUserRole) }}
                                                    </Badge>
                                                    <p class="text-xs text-gray-600 mt-2">
                                                        {{ getRoleDescription(currentUserRole) }}
                                                    </p>
                                                </div>

                                                <Separator />

                                                <div>
                                                    <Label class="text-xs font-medium text-gray-500 uppercase tracking-wide">Berechtigungen</Label>
                                                    <div class="mt-2 space-y-1">
                                                        <div v-for="permission in displayPermissions.slice(0, 4)" :key="permission"
                                                             class="flex items-center gap-2 text-xs text-gray-700">
                                                            <div class="w-1.5 h-1.5 bg-green-500 rounded-full"></div>
                                                            {{ formatPermission(permission) }}
                                                        </div>
                                                        <div v-if="currentUserPermissions.length > 4" class="text-xs text-gray-500">
                                                            ...und {{ currentUserPermissions.length - 4 }} weitere
                                                        </div>
                                                    </div>
                                                </div>
                                            </CardContent>
                                        </Card>

                                        <!-- Case Statistics -->
                                        <Card>
                                            <CardHeader>
                                                <CardTitle class="text-lg flex items-center gap-2">
                                                    <BarChart3 class="w-5 h-5" />
                                                    Statistiken
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent class="space-y-4">
                                                <div class="grid grid-cols-2 gap-4">
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold text-gray-900">{{ participants.length }}</div>
                                                        <div class="text-xs text-gray-500">Teilnehmer</div>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="text-2xl font-bold text-gray-900">{{ getDaysActive() }}</div>
                                                        <div class="text-xs text-gray-500">Tage aktiv</div>
                                                    </div>
                                                </div>

                                                <Separator />

                                                <div class="space-y-2 text-xs">
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-500">Erstellt</span>
                                                        <span class="text-gray-900">{{ formatDate(caseFile.created_at) }}</span>
                                                    </div>
                                                    <div class="flex justify-between">
                                                        <span class="text-gray-500">Aktualisiert</span>
                                                        <span class="text-gray-900">{{ formatDate(caseFile.updated_at) }}</span>
                                                    </div>
                                                    <div v-if="caseReference" class="flex justify-between">
                                                        <span class="text-gray-500">Datenbank</span>
                                                        <Badge :variant="caseReference.is_active ? 'default' : 'destructive'" class="text-xs">
                                                            {{ caseReference.is_active ? 'Aktiv' : 'Inaktiv' }}
                                                        </Badge>
                                                    </div>
                                                </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </TabsContent>

                            <!-- Participants Tab -->
                            <TabsContent value="participants" class="px-6 py-8">
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">Verfahrensbeteiligte</h3>
                                        <p class="text-sm text-gray-600">Alle am Verfahren beteiligten Personen und ihre Rollen</p>
                                    </div>
                                    <Button
                                        v-if="canManageParticipants"
                                        @click="showAddParticipant = true"
                                    >
                                        <Plus class="w-4 h-4 mr-2" />
                                        Teilnehmer hinzufügen
                                    </Button>
                                </div>
                                <div v-if="participants.length === 0" class="text-center py-16">
                                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <Users class="w-8 h-8 text-gray-400" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Noch keine Teilnehmer</h3>
                                    <p class="text-gray-600 mb-6">Fügen Sie Teilnehmer hinzu, um das Verfahren zu starten.</p>
                                    <Button
                                        v-if="canManageParticipants"
                                        @click="showAddParticipant = true"
                                    >
                                        <Plus class="w-4 h-4 mr-2" />
                                        Ersten Teilnehmer hinzufügen
                                    </Button>
                                </div>

                                <div v-else class="space-y-6">
                                    <!-- Grouped Participants -->
                                    <div v-for="(group, groupName) in groupedParticipants" :key="groupName" class="space-y-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-lg border">
                                                <component :is="getGroupIcon(groupName)" class="w-4 h-4 text-gray-600" />
                                                <h4 class="text-sm font-semibold text-gray-900">{{ getGroupName(groupName) }}</h4>
                                                <Badge variant="outline" class="text-xs">{{ group.length }}</Badge>
                                            </div>
                                        </div>

                                        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                                            <Card v-for="participant in group" :key="participant.id"
                                                  class="group hover:shadow-md transition-all duration-200">
                                                <CardContent class="p-4">
                                                    <div class="flex items-start gap-3">
                                                        <div class="relative">
                                                            <Avatar class="w-12 h-12">
                                                                <AvatarImage
                                                                    v-if="participant.user.avatar_url"
                                                                    :src="participant.user.avatar_url"
                                                                    :alt="participant.user.name"
                                                                />
                                                                <AvatarFallback class="bg-blue-600 text-white font-semibold">
                                                                    {{ getInitials(participant.user.name) }}
                                                                </AvatarFallback>
                                                            </Avatar>
                                                            <div v-if="participant.is_primary"
                                                                 class="absolute -top-1 -right-1 bg-yellow-400 rounded-full p-1">
                                                                <Crown class="w-3 h-3 text-white" />
                                                            </div>
                                                        </div>

                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-center gap-2 mb-2">
                                                                <h5 class="text-sm font-semibold text-gray-900 truncate">{{ participant.user.name }}</h5>
                                                                <Badge :variant="getRoleVariant(participant.role)" class="text-xs">
                                                                    {{ participant.role_display }}
                                                                </Badge>
                                                            </div>

                                                            <p class="text-xs text-gray-600 mb-1">{{ participant.user.email }}</p>

                                                            <div v-if="participant.user.title || participant.user.law_firm" class="text-xs text-gray-500 space-y-0.5">
                                                                <p v-if="participant.user.title">{{ participant.user.title }}</p>
                                                                <p v-if="participant.user.law_firm">{{ participant.user.law_firm }}</p>
                                                            </div>

                                                            <div class="mt-3 flex items-center justify-between">
                                                                <Tooltip>
                                                                    <TooltipTrigger>
                                                                        <div class="flex items-center gap-1 text-xs text-gray-500">
                                                                            <Shield class="w-3 h-3" />
                                                                            {{ participant.permissions.length }} Berechtigungen
                                                                        </div>
                                                                    </TooltipTrigger>
                                                                    <TooltipContent class="max-w-xs">
                                                                        <div class="text-xs space-y-2">
                                                                            <p class="font-semibold">{{ participant.role_description }}</p>
                                                                            <div>
                                                                                <p class="font-medium mb-1">Berechtigungen:</p>
                                                                                <ul class="space-y-0.5">
                                                                                    <li v-for="permission in participant.permissions.slice(0, 5)" :key="permission">
                                                                                        • {{ formatPermission(permission) }}
                                                                                    </li>
                                                                                    <li v-if="participant.permissions.length > 5" class="text-gray-500">
                                                                                        ...und {{ participant.permissions.length - 5 }} weitere
                                                                                    </li>
                                                                                </ul>
                                                                            </div>
                                                                        </div>
                                                                    </TooltipContent>
                                                                </Tooltip>

                                                                <Button
                                                                    v-if="canManageParticipants"
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    class="opacity-0 group-hover:opacity-100 transition-opacity w-8 h-8 p-0"
                                                                    @click="editParticipant(participant)"
                                                                >
                                                                    <Edit class="w-3 h-3" />
                                                                </Button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </CardContent>
                                            </Card>
                                        </div>
                                    </div>
                                </div>
                            </TabsContent>

                            <!-- Timeline Tab -->
                            <TabsContent value="timeline" class="px-6 py-8">
                                <div class="text-center py-16">
                                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <Clock class="w-8 h-8 text-gray-400" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Verfahrensverlauf</h3>
                                    <p class="text-gray-600">Timeline wird in einer zukünftigen Version implementiert.</p>
                                </div>
                            </TabsContent>

                            <!-- Documents Tab -->
                            <TabsContent value="documents" class="px-6 py-8">
                                <div class="text-center py-16">
                                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <FolderOpen class="w-8 h-8 text-gray-400" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Dokumente</h3>
                                    <p class="text-gray-600">Dokumentenverwaltung wird in einer zukünftigen Version implementiert.</p>
                                </div>
                            </TabsContent>

                            <!-- Settings Tab -->
                            <TabsContent value="settings" class="px-6 py-8">
                                <div class="text-center py-16">
                                    <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                        <Settings class="w-8 h-8 text-gray-400" />
                                    </div>
                                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Einstellungen</h3>
                                    <p class="text-gray-600">Erweiterte Einstellungen werden in einer zukünftigen Version implementiert.</p>
                                </div>
                            </TabsContent>
                        </Tabs>
                    </div>
                </div>

                <!-- Advanced Participant Management Dialogs -->

                <!-- Add Participant Dialog -->
                <Dialog v-model:open="showAddParticipant">
                    <DialogContent class="max-w-4xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-3 text-xl">
                                <div class="p-2 bg-blue-100 rounded-lg">
                                    <UserPlus class="w-6 h-6 text-blue-600" />
                                </div>
                                Neuen Teilnehmer hinzufügen
                            </DialogTitle>
                            <DialogDescription>
                                Fügen Sie eine neue Person zum Schiedsverfahren hinzu. Wählen Sie die entsprechende Rolle und Berechtigungen aus.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="space-y-8 py-6">
                            <!-- User Selection -->
                            <div class="space-y-4">
                                <Label class="text-base font-semibold flex items-center gap-2">
                                    <Search class="h-4 w-4" />
                                    Benutzer auswählen
                                </Label>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-3">
                                        <Input
                                            v-model="participantForm.userSearch"
                                            placeholder="Nach Name oder E-Mail suchen..."
                                            class="h-12"
                                            @input="searchUsers"
                                        />
                                        <div v-if="searchResults.length > 0" class="max-h-40 overflow-y-auto border rounded-lg">
                                            <div
                                                v-for="user in searchResults"
                                                :key="user.id"
                                                class="p-3 hover:bg-gray-50 cursor-pointer border-b last:border-b-0 flex items-center gap-3"
                                                @click="selectUser(user)"
                                            >
                                                <Avatar class="h-8 w-8">
                                                    <AvatarImage v-if="user.avatar_url" :src="user.avatar_url" :alt="user.name" />
                                                    <AvatarFallback class="bg-gradient-to-br from-blue-500 to-purple-500 text-white text-xs">
                                                        {{ getInitials(user.name) }}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div class="flex-1">
                                                    <p class="font-medium text-sm">{{ user.name }}</p>
                                                    <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-if="participantForm.selectedUser" class="bg-gradient-to-br from-blue-50 to-purple-50 p-4 rounded-xl border border-blue-200">
                                        <Label class="text-sm font-medium text-blue-900 mb-3 block">Ausgewählter Benutzer</Label>
                                        <div class="flex items-center gap-3">
                                            <Avatar class="h-12 w-12">
                                                <AvatarImage v-if="participantForm.selectedUser.avatar_url" :src="participantForm.selectedUser.avatar_url" :alt="participantForm.selectedUser.name" />
                                                <AvatarFallback class="bg-gradient-to-br from-blue-500 to-purple-500 text-white font-semibold">
                                                    {{ getInitials(participantForm.selectedUser.name) }}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div class="flex-1">
                                                <h4 class="font-semibold text-blue-900">{{ participantForm.selectedUser.name }}</h4>
                                                <p class="text-sm text-blue-700">{{ participantForm.selectedUser.email }}</p>
                                                <p v-if="participantForm.selectedUser.title" class="text-xs text-blue-600">{{ participantForm.selectedUser.title }}</p>
                                            </div>
                                            <Button variant="ghost" size="sm" @click="clearUser" class="text-blue-600 hover:text-blue-800">
                                                <X class="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Role Selection -->
                            <div class="space-y-4">
                                <Label class="text-base font-semibold flex items-center gap-2">
                                    <UserCheck class="h-4 w-4" />
                                    Rolle im Verfahren
                                </Label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <Card
                                        v-for="role in availableRoles"
                                        :key="role.id"
                                        class="cursor-pointer transition-all duration-200 hover:shadow-md"
                                        :class="[
                                            participantForm.role === role.id
                                                ? 'ring-2 ring-blue-500 bg-blue-50 border-blue-200'
                                                : 'hover:border-gray-300'
                                        ]"
                                        @click="participantForm.role = role.id"
                                    >
                                        <CardContent class="p-4 text-center">
                                            <component :is="role.icon" class="h-8 w-8 mx-auto mb-2" :class="role.iconColor" />
                                            <h4 class="font-semibold text-sm mb-1">{{ role.name }}</h4>
                                            <p class="text-xs text-muted-foreground">{{ role.description }}</p>
                                            <Badge v-if="participantForm.role === role.id" variant="default" class="mt-2 text-xs">
                                                <Check class="h-3 w-3 mr-1" />
                                                Ausgewählt
                                            </Badge>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>

                            <!-- Primary Role Toggle -->
                            <div v-if="canSetPrimary" class="space-y-3">
                                <Label class="text-base font-semibold flex items-center gap-2">
                                    <Crown class="h-4 w-4" />
                                    Führungsrolle
                                </Label>
                                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-4 rounded-xl border border-yellow-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-lg">
                                                <Crown class="h-5 w-5 text-white" />
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-yellow-900">Als primäre/n {{ getRoleName(participantForm.role) }} festlegen</h4>
                                                <p class="text-sm text-yellow-700">Diese Person wird die Hauptverantwortung für diese Rolle übernehmen</p>
                                            </div>
                                        </div>
                                        <Switch v-model:checked="participantForm.isPrimary" />
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Settings -->
                            <div class="space-y-4">
                                <Label class="text-base font-semibold flex items-center gap-2">
                                    <Settings class="h-4 w-4" />
                                    Zusätzliche Einstellungen
                                </Label>
                                <div class="space-y-4">
                                    <div class="space-y-2">
                                        <Label for="notes">Notizen (optional)</Label>
                                        <Textarea
                                            id="notes"
                                            v-model="participantForm.notes"
                                            placeholder="Zusätzliche Informationen zur Rolle oder Ernennung..."
                                            rows="3"
                                        />
                                    </div>
                                    <div class="space-y-2">
                                        <Label for="appointed_at">Ernennungsdatum</Label>
                                        <Input
                                            id="appointed_at"
                                            v-model="participantForm.appointedAt"
                                            type="datetime-local"
                                            class="h-12"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="gap-3">
                            <Button variant="outline" @click="showAddParticipant = false">
                                <X class="mr-2 h-4 w-4" />
                                Abbrechen
                            </Button>
                            <Button
                                @click="addParticipant"
                                :disabled="!participantForm.selectedUser || !participantForm.role"
                                class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-700 hover:to-pink-700"
                            >
                                <UserPlus class="mr-2 h-4 w-4" />
                                Teilnehmer hinzufügen
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <!-- Edit Participant Dialog -->
                <Dialog v-model:open="showEditParticipant">
                    <DialogContent class="max-w-3xl max-h-[90vh] overflow-y-auto">
                        <DialogHeader>
                            <DialogTitle class="flex items-center gap-3 text-xl">
                                <div class="p-2 bg-gradient-to-r from-blue-100 to-indigo-100 rounded-lg">
                                    <UserCog class="h-6 w-6 text-blue-600" />
                                </div>
                                Teilnehmer bearbeiten
                            </DialogTitle>
                            <DialogDescription>
                                Bearbeiten Sie die Rolle und Einstellungen für {{ editingParticipant?.user?.name }}
                            </DialogDescription>
                        </DialogHeader>

                        <div v-if="editingParticipant" class="space-y-6 py-6">
                            <!-- User Info (Read-only) -->
                            <div class="bg-gradient-to-br from-slate-50 to-gray-50 p-4 rounded-xl border">
                                <Label class="text-sm font-medium text-slate-700 mb-3 block">Teilnehmer</Label>
                                <div class="flex items-center gap-4">
                                    <Avatar class="h-16 w-16">
                                        <AvatarImage v-if="editingParticipant.user.avatar_url" :src="editingParticipant.user.avatar_url" :alt="editingParticipant.user.name" />
                                        <AvatarFallback class="bg-gradient-to-br from-blue-500 to-purple-500 text-white text-lg font-bold">
                                            {{ getInitials(editingParticipant.user.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-bold text-slate-900">{{ editingParticipant.user.name }}</h4>
                                        <p class="text-slate-600">{{ editingParticipant.user.email }}</p>
                                        <div v-if="editingParticipant.user.title || editingParticipant.user.law_firm" class="text-sm text-slate-500 mt-1">
                                            <p v-if="editingParticipant.user.title">{{ editingParticipant.user.title }}</p>
                                            <p v-if="editingParticipant.user.law_firm">{{ editingParticipant.user.law_firm }}</p>
                                        </div>
                                    </div>
                                    <Badge :variant="getRoleVariant(editingParticipant.role)" class="self-start">
                                        {{ editingParticipant.role_display }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Role Update -->
                            <div class="space-y-4">
                                <Label class="text-base font-semibold flex items-center gap-2">
                                    <RefreshCw class="h-4 w-4" />
                                    Rolle ändern
                                </Label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                                    <Card
                                        v-for="role in availableRoles"
                                        :key="role.id"
                                        class="cursor-pointer transition-all duration-200 hover:shadow-md"
                                        :class="[
                                            editForm.role === role.id
                                                ? 'ring-2 ring-blue-500 bg-blue-50 border-blue-200'
                                                : 'hover:border-gray-300'
                                        ]"
                                        @click="editForm.role = role.id"
                                    >
                                        <CardContent class="p-3 text-center">
                                            <component :is="role.icon" class="h-6 w-6 mx-auto mb-2" :class="role.iconColor" />
                                            <h4 class="font-semibold text-xs mb-1">{{ role.name }}</h4>
                                            <Badge v-if="editForm.role === role.id" variant="default" class="text-xs">
                                                <Check class="h-3 w-3 mr-1" />
                                                Ausgewählt
                                            </Badge>
                                        </CardContent>
                                    </Card>
                                </div>
                            </div>

                            <!-- Primary Status -->
                            <div v-if="canSetPrimary" class="space-y-3">
                                <Label class="text-base font-semibold flex items-center gap-2">
                                    <Crown class="h-4 w-4" />
                                    Führungsrolle
                                </Label>
                                <div class="bg-gradient-to-br from-yellow-50 to-orange-50 p-4 rounded-xl border border-yellow-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3">
                                            <div class="p-2 bg-gradient-to-r from-yellow-400 to-orange-400 rounded-lg">
                                                <Crown class="h-5 w-5 text-white" />
                                            </div>
                                            <div>
                                                <h4 class="font-semibold text-yellow-900">Primäre Rolle</h4>
                                                <p class="text-sm text-yellow-700">Hauptverantwortung für diese Rolle</p>
                                            </div>
                                        </div>
                                        <Switch v-model:checked="editForm.isPrimary" />
                                    </div>
                                </div>
                            </div>

                            <!-- Update Notes -->
                            <div class="space-y-3">
                                <Label for="edit_notes" class="text-base font-semibold flex items-center gap-2">
                                    <FileText class="h-4 w-4" />
                                    Notizen
                                </Label>
                                <Textarea
                                    id="edit_notes"
                                    v-model="editForm.notes"
                                    placeholder="Zusätzliche Informationen..."
                                    rows="4"
                                />
                            </div>

                            <!-- Danger Zone -->
                            <div class="space-y-3">
                                <Label class="text-base font-semibold flex items-center gap-2 text-red-700">
                                    <AlertTriangle class="h-4 w-4" />
                                    Gefährliche Aktionen
                                </Label>
                                <div class="bg-gradient-to-br from-red-50 to-pink-50 p-4 rounded-xl border border-red-200">
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        @click="removeParticipant"
                                        class="bg-gradient-to-r from-red-500 to-pink-500 hover:from-red-600 hover:to-pink-600"
                                    >
                                        <Trash2 class="mr-2 h-4 w-4" />
                                        Teilnehmer entfernen
                                    </Button>
                                    <p class="text-xs text-red-600 mt-2">
                                        Diese Aktion kann nicht rückgängig gemacht werden
                                    </p>
                                </div>
                            </div>
                        </div>

                        <DialogFooter class="gap-3">
                            <Button variant="outline" @click="showEditParticipant = false">
                                <X class="mr-2 h-4 w-4" />
                                Abbrechen
                            </Button>
                            <Button
                                @click="updateParticipant"
                                class="bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700"
                            >
                                <Save class="mr-2 h-4 w-4" />
                                Änderungen speichern
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
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
import { Input } from '@/components/ui/input'
import Textarea from '@/components/ui/textarea.vue'
import Switch from '@/components/ui/switch.vue'
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
import { Separator } from '@/components/ui/separator'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Progress } from '@/components/ui/progress'
import {
    Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter
} from '@/components/ui/dialog'
import {
    ArrowLeft, Edit, FileText, Trash2, Users, Shield, Plus, Crown, Check,
    Upload, MessageSquare, Scale, Building, User, Settings, Clock, FolderOpen,
    Calendar, Euro, Tag, Zap, AlertTriangle, BarChart3,
    CheckCircle, PlayCircle, PauseCircle, XCircle, Clock3, UserPlus, UserCheck,
    UserCog, Search, X, Save, RefreshCw, Gavel, Briefcase, Users2, Brain
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
const showEditParticipant = ref(false)
const editingParticipant = ref(null)

// Advanced Participant Management State
const participantForm = ref({
    userSearch: '',
    selectedUser: null,
    role: '',
    isPrimary: false,
    notes: '',
    appointedAt: new Date().toISOString().slice(0, 16)
})

const editForm = ref({
    role: '',
    isPrimary: false,
    notes: ''
})

const searchResults = ref([])

// Available roles for participant management
const availableRoles = ref([
    {
        id: 'chairman',
        name: 'Vorsitzender',
        description: 'Leitung des Schiedsgerichts',
        icon: Crown,
        iconColor: 'text-yellow-600'
    },
    {
        id: 'referee',
        name: 'Schiedsrichter',
        description: 'Entscheidung über Streitigkeiten',
        icon: Gavel,
        iconColor: 'text-blue-600'
    },
    {
        id: 'co_referee',
        name: 'Co-Schiedsrichter',
        description: 'Unterstützung des Hauptschiedsrichters',
        icon: Scale,
        iconColor: 'text-indigo-600'
    },
    {
        id: 'claimant',
        name: 'Kläger',
        description: 'Antragstellende Partei',
        icon: User,
        iconColor: 'text-green-600'
    },
    {
        id: 'respondent',
        name: 'Beklagte',
        description: 'Antragsgegnerin',
        icon: Shield,
        iconColor: 'text-red-600'
    },
    {
        id: 'expert',
        name: 'Sachverständiger',
        description: 'Fachliche Expertise',
        icon: Brain,
        iconColor: 'text-purple-600'
    },
    {
        id: 'witness',
        name: 'Zeuge',
        description: 'Zeugnis ablegen',
        icon: Users2,
        iconColor: 'text-orange-600'
    },
    {
        id: 'admin',
        name: 'Administrator',
        description: 'Verwaltung des Verfahrens',
        icon: Settings,
        iconColor: 'text-gray-600'
    }
])

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

const getDaysActive = () => {
    const created = new Date(props.caseFile.created_at)
    const now = new Date()
    const diffTime = Math.abs(now - created)
    return Math.ceil(diffTime / (1000 * 60 * 60 * 24))
}

const formatRelativeDate = (dateString) => {
    if (!dateString) return ''
    const date = new Date(dateString)
    const now = new Date()
    const diffTime = now - date
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24))

    if (diffDays === 0) return 'heute'
    if (diffDays === 1) return 'gestern'
    if (diffDays < 7) return `vor ${diffDays} Tagen`
    if (diffDays < 30) return `vor ${Math.floor(diffDays / 7)} Wochen`
    return `vor ${Math.floor(diffDays / 30)} Monaten`
}

const getStatusProgress = (status) => {
    const statusProgress = {
        draft: 10,
        initiated: 25,
        pending: 40,
        statement_of_claim: 50,
        statement_of_defense: 60,
        evidence_exchange: 70,
        hearing_scheduled: 80,
        under_deliberation: 90,
        decided: 100,
        closed: 100,
        suspended: 50,
        settled: 100
    }
    return statusProgress[status] || 0
}

const getStatusIcon = (status) => {
    const icons = {
        draft: Edit,
        initiated: PlayCircle,
        pending: Clock3,
        statement_of_claim: FileText,
        statement_of_defense: FileText,
        evidence_exchange: Upload,
        hearing_scheduled: Calendar,
        under_deliberation: Clock,
        decided: CheckCircle,
        closed: CheckCircle,
        suspended: PauseCircle,
        settled: CheckCircle
    }
    return icons[status] || Clock
}

const getComplexityVariant = (complexity) => {
    const variants = {
        simple: 'default',
        medium: 'secondary',
        high: 'destructive',
        very_high: 'destructive'
    }
    return variants[complexity] || 'outline'
}

const formatComplexity = (complexity) => {
    const labels = {
        simple: 'Einfach',
        medium: 'Mittel',
        high: 'Hoch',
        very_high: 'Sehr hoch'
    }
    return labels[complexity] || complexity
}

const getUrgencyVariant = (urgency) => {
    const variants = {
        normal: 'outline',
        urgent: 'secondary',
        very_urgent: 'destructive',
        critical: 'destructive'
    }
    return variants[urgency] || 'outline'
}

const formatUrgency = (urgency) => {
    const labels = {
        normal: 'Normal',
        urgent: 'Dringend',
        very_urgent: 'Sehr dringend',
        critical: 'Kritisch'
    }
    return labels[urgency] || urgency
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

// Advanced Participant Management Functions

// Computed properties for dialog functionality
const canSetPrimary = computed(() => {
    return ['chairman', 'referee', 'claimant', 'respondent'].includes(participantForm.value.role) ||
           ['chairman', 'referee', 'claimant', 'respondent'].includes(editForm.value.role)
})

// User search functionality
const searchUsers = async () => {
    if (participantForm.value.userSearch.length < 2) {
        searchResults.value = []
        return
    }

    // Mock user search - replace with actual API call
    const mockUsers = [
        { id: 1, name: 'Dr. Max Mustermann', email: 'max.mustermann@law.de', title: 'Rechtsanwalt', avatar_url: null },
        { id: 2, name: 'Prof. Anna Schmidt', email: 'anna.schmidt@uni.de', title: 'Professorin', avatar_url: null },
        { id: 3, name: 'Hans Weber', email: 'hans.weber@consulting.de', title: 'Berater', avatar_url: null },
        { id: 4, name: 'Lisa Mueller', email: 'lisa.mueller@company.com', title: 'Geschäftsführerin', avatar_url: null },
    ]

    searchResults.value = mockUsers.filter(user =>
        user.name.toLowerCase().includes(participantForm.value.userSearch.toLowerCase()) ||
        user.email.toLowerCase().includes(participantForm.value.userSearch.toLowerCase())
    )
}

const selectUser = (user) => {
    participantForm.value.selectedUser = user
    participantForm.value.userSearch = user.name
    searchResults.value = []
}

const clearUser = () => {
    participantForm.value.selectedUser = null
    participantForm.value.userSearch = ''
}

const getRoleName = (roleId) => {
    const role = availableRoles.value.find(r => r.id === roleId)
    return role ? role.name : roleId
}

const resetParticipantForm = () => {
    participantForm.value = {
        userSearch: '',
        selectedUser: null,
        role: '',
        isPrimary: false,
        notes: '',
        appointedAt: new Date().toISOString().slice(0, 16)
    }
}

const addParticipant = async () => {
    try {
        // Mock API call - replace with actual implementation
        console.log('Adding participant:', {
            user: participantForm.value.selectedUser,
            role: participantForm.value.role,
            isPrimary: participantForm.value.isPrimary,
            notes: participantForm.value.notes,
            appointedAt: participantForm.value.appointedAt
        })

        // TODO: Implement actual API call
        // await router.post(participantStore.url({ case: props.caseFile.id }), {
        //     user_id: participantForm.value.selectedUser.id,
        //     role: participantForm.value.role,
        //     is_primary: participantForm.value.isPrimary,
        //     notes: participantForm.value.notes,
        //     appointed_at: participantForm.value.appointedAt
        // })

        showAddParticipant.value = false
        resetParticipantForm()

        // Show success message
        alert('Teilnehmer erfolgreich hinzugefügt!')

    } catch (error) {
        console.error('Error adding participant:', error)
        alert('Fehler beim Hinzufügen des Teilnehmers')
    }
}

const editParticipant = (participant) => {
    editingParticipant.value = participant
    editForm.value = {
        role: participant.role,
        isPrimary: participant.is_primary,
        notes: participant.notes || ''
    }
    showEditParticipant.value = true
}

const updateParticipant = async () => {
    try {
        // Mock API call - replace with actual implementation
        console.log('Updating participant:', {
            participant: editingParticipant.value,
            updates: editForm.value
        })

        // TODO: Implement actual API call
        // await router.put(participantUpdate.url({
        //     case: props.caseFile.id,
        //     participant: editingParticipant.value.id
        // }), {
        //     role: editForm.value.role,
        //     is_primary: editForm.value.isPrimary,
        //     notes: editForm.value.notes
        // })

        showEditParticipant.value = false
        editingParticipant.value = null

        // Show success message
        alert('Teilnehmer erfolgreich aktualisiert!')

    } catch (error) {
        console.error('Error updating participant:', error)
        alert('Fehler beim Aktualisieren des Teilnehmers')
    }
}

const removeParticipant = async () => {
    if (!editingParticipant.value) return

    const confirmed = confirm(`Sind Sie sicher, dass Sie ${editingParticipant.value.user.name} aus dem Verfahren entfernen möchten?`)
    if (!confirmed) return

    try {
        // Mock API call - replace with actual implementation
        console.log('Removing participant:', editingParticipant.value)

        // TODO: Implement actual API call
        // await router.delete(participantDestroy.url({
        //     case: props.caseFile.id,
        //     participant: editingParticipant.value.id
        // }))

        showEditParticipant.value = false
        editingParticipant.value = null

        // Show success message
        alert('Teilnehmer erfolgreich entfernt!')

    } catch (error) {
        console.error('Error removing participant:', error)
        alert('Fehler beim Entfernen des Teilnehmers')
    }
}

const deleteCase = () => {
    if (confirm('Sind Sie sicher, dass Sie diesen Fall löschen möchten? Dies wird auch die zugehörige Datenbank löschen.')) {
        router.delete(destroy.url({ case: props.caseFile.id }))
    }
}
</script>