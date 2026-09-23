<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anj's Gift Manager</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css?v=6">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="min-h-screen antialiased text-[#1C1917]" x-data="giftApp()" x-init="fetchData()">

    <!-- Sticky Header -->
    <header class="sticky top-0 z-40 bg-[#FAF6EF]/90 backdrop-blur-md border-b border-[#ECE5D8] px-6 py-4 shadow-2xs">
        <div class="max-w-7xl mx-auto flex flex-wrap justify-between items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="text-3xl">🎁</span>
                <h1 class="text-2xl font-bold font-serif tracking-tight text-[#1C1917]">Anj's Gift Manage</h1>

                <!-- Global Year Selector -->
                <div class="flex items-center bg-[#EDE7DC] rounded-full px-2 py-0.5 border border-[#DFD8CC] ml-2">
                    <button @click="changeYear(selectedYear - 1)" class="px-2 py-0.5 text-xs text-stone-600 hover:text-black transition">◀</button>
                    <span class="px-1 text-xs font-semibold text-stone-800" x-text="selectedYear"></span>
                    <button @click="changeYear(selectedYear + 1)" class="px-2 py-0.5 text-xs text-stone-600 hover:text-black transition">▶</button>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <!-- Search Bar -->
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-stone-400 text-sm">🔍</span>
                    <input type="text" x-model="search" placeholder="Search people..." 
                           class="pl-9 pr-4 py-2 text-sm bg-white border border-[#E3DCDB] rounded-full w-48 sm:w-64 focus:outline-none focus:ring-2 focus:ring-[#EAA23B]/60 shadow-2xs">
                </div>

                <button @click="showImportModal = true" class="hidden sm:inline-flex bg-white hover:bg-stone-50 text-stone-700 border border-[#E3DCDB] px-3.5 py-2 rounded-full text-xs font-semibold transition">
                    📥 Import
                </button>
                <button @click="openEventModal()" class="hidden sm:inline-flex bg-white hover:bg-stone-50 text-stone-700 border border-[#E3DCDB] px-3.5 py-2 rounded-full text-xs font-semibold transition">
                    + Event
                </button>
                <button @click="openPersonModal()" class="bg-[#E66A55] hover:bg-[#D75944] text-white px-4 py-2 rounded-full text-xs font-semibold shadow-xs transition">
                    + Person
                </button>
                <button @click="openGiftModal()" class="bg-[#EAA23B] hover:bg-[#D9922C] text-white px-4 py-2 rounded-full text-xs font-semibold shadow-xs transition">
                    + Gift
                </button>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-6 py-6 space-y-6">

        <!-- Top Dashboard: Christmas Anchor + Upcoming Birthdays -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5 items-stretch">
            
            <!-- Left Hero Spotlight: Dedicated to Christmas -->
            <div class="lg:col-span-2 bg-[#231C18] text-white rounded-app p-6 flex flex-col justify-between shadow-xs">
                <div>
                    <div class="flex justify-between items-start">
                        <div>
                            <span class="text-[10px] tracking-widest uppercase font-semibold text-stone-400 block mb-1">MAIN OCCASION</span>
                            <h2 class="text-2xl sm:text-3xl font-bold font-serif tracking-tight" x-text="`Christmas ${selectedYear}`"></h2>
                        </div>
                        <div class="text-right">
                            <div class="text-3xl sm:text-4xl font-serif text-[#F2A93B] leading-none" x-text="daysUntilChristmas"></div>
                            <span class="text-[11px] text-stone-400 font-medium">days away</span>
                        </div>
                    </div>

                    <!-- Embedded Christmas & People Summary Metrics -->
                    <div class="mt-5 grid grid-cols-2 gap-4 border-t border-[#352D26] pt-4">
                        <div class="flex items-center gap-2.5">
                            <span class="text-lg">👥</span>
                            <div>
                                <div class="text-lg font-bold font-serif text-stone-100 leading-tight" x-text="people.length"></div>
                                <div class="text-[11px] text-stone-400">People Tracked</div>
                            </div>
                        </div>
                        <div class="flex items-center gap-2.5">
                            <span class="text-lg">🎀</span>
                            <div>
                                <div class="text-lg font-bold font-serif text-stone-100 leading-tight" x-text="christmasStats.assigned"></div>
                                <div class="text-[11px] text-stone-400">Christmas Gifts</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Christmas Gift Progress Bar -->
                <div class="mt-6 space-y-2">
                    <div class="w-full bg-[#39322D] h-2 rounded-full overflow-hidden">
                        <div class="bg-[#5FA593] h-full rounded-full transition-all duration-500" :style="`width: ${christmasStats.percent}%`"></div>
                    </div>
                    <div class="flex justify-between items-center text-xs text-stone-400">
                        <span x-text="`${christmasStats.percent}% sorted (Purchased, Wrapped, or Given)`"></span>
                        <span class="font-medium text-stone-300" x-text="`${christmasStats.sorted}/${christmasStats.assigned || people.length}`"></span>
                    </div>
                </div>
            </div>

            <!-- Right Spotlight: Upcoming Birthdays (Next 3 Months) -->
            <div class="bg-white rounded-app border border-[#ECE5D8] p-5 flex flex-col justify-between shadow-2xs">
                <div>
                    <div class="flex justify-between items-center mb-3">
                        <h3 class="font-serif font-bold text-stone-900 text-sm flex items-center gap-1.5">
                            <span>🎂</span> Upcoming Birthdays
                        </h3>
                        <span class="text-[10px] uppercase font-bold tracking-wider text-stone-400 bg-stone-100 px-2 py-0.5 rounded-full">Next 3 Mos</span>
                    </div>

                    <!-- Birthdays List -->
                    <div class="space-y-2.5 max-h-52 overflow-y-auto pr-1">
                        <template x-for="item in upcomingBirthdays" :key="item.person.id">
                            <div class="flex items-center justify-between p-2 rounded-xl bg-[#FAF7F2] border border-[#F2ECE3] text-xs">
                                <div class="flex items-center gap-2.5 truncate">
                                    <div class="w-7 h-7 rounded-full flex items-center justify-center text-white font-bold text-xs shrink-0"
                                         :class="getAvatarColorClass(item.person.id)">
                                        <span x-text="item.person.full_name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="truncate">
                                        <div class="font-semibold text-stone-900 truncate" x-text="item.person.full_name"></div>
                                        <div class="text-[11px] text-stone-500" x-text="item.formattedDate + (item.turningAge ? ` (turning ${item.turningAge})` : '')"></div>
                                    </div>
                                </div>
                                <div class="text-right shrink-0 pl-2">
                                    <span class="text-[11px] font-semibold text-[#B46C14] bg-[#FFF6E7] border border-[#F8E0B7] px-2 py-0.5 rounded-full"
                                          x-text="item.daysUntil === 0 ? 'Today!' : `${item.daysUntil}d away`">
                                    </span>
                                </div>
                            </div>
                        </template>

                        <div x-show="upcomingBirthdays.length === 0" class="text-xs text-stone-400 italic text-center py-6">
                            No birthdays in the next 3 months.
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-[#F5EFE6] text-[11px] text-stone-400 flex justify-between items-center">
                    <span x-text="`${upcomingBirthdays.length} upcoming birthday${upcomingBirthdays.length === 1 ? '' : 's'}`"></span>
                    <button @click="openPersonModal()" class="text-stone-700 hover:underline font-semibold">+ Add Birthday</button>
                </div>
            </div>

        </div>

        <!-- Filter Row & View Switcher -->
        <div class="flex flex-wrap justify-between items-center gap-4 pt-2">
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span class="text-[11px] font-bold text-stone-400 tracking-wider mr-1 uppercase">FILTER:</span>
                
                <select x-model="filters.relationships" class="bg-white border border-[#E3DCDB] rounded-lg px-3 py-1.5 text-stone-700 focus:outline-none shadow-2xs cursor-pointer">
                    <option value="">All relationships</option>
                    <template x-for="rel in availableRelationships" :key="rel">
                        <option :value="rel" x-text="rel"></option>
                    </template>
                </select>

                <select x-model="filters.groups" class="bg-white border border-[#E3DCDB] rounded-lg px-3 py-1.5 text-stone-700 focus:outline-none shadow-2xs cursor-pointer">
                    <option value="">All groups</option>
                    <template x-for="g in existingGroups" :key="g.id">
                        <option :value="g.name" x-text="g.name"></option>
                    </template>
                </select>

                <select x-model="filters.status" class="bg-white border border-[#E3DCDB] rounded-lg px-3 py-1.5 text-stone-700 focus:outline-none shadow-2xs cursor-pointer">
                    <option value="">All gift statuses</option>
                    <option value="Idea">Idea</option>
                    <option value="Purchased">Purchased</option>
                    <option value="Wrapped">Wrapped</option>
                    <option value="Given">Given</option>
                    <option value="no_gifts">Missing Gift</option>
                </select>

                <button x-show="hasActiveFilters" @click="resetFilters()" class="text-xs text-stone-500 hover:text-stone-800 underline ml-1">
                    Clear
                </button>
            </div>

            <!-- Segmented View Switcher -->
            <div class="flex items-center bg-[#EDE7DC] rounded-xl p-1 border border-[#DFD8CC] text-xs font-semibold">
                <button @click="activeView = 'standard'" class="px-3 py-1 rounded-lg transition"
                        :class="activeView === 'standard' ? 'bg-[#1C1917] text-white shadow-xs' : 'text-stone-600 hover:text-stone-900'">
                    ▦ Cards
                </button>
                <button @click="activeView = 'compact'" class="px-3 py-1 rounded-lg transition"
                        :class="activeView === 'compact' ? 'bg-[#1C1917] text-white shadow-xs' : 'text-stone-600 hover:text-stone-900'">
                    ≡ List
                </button>
                <button @click="activeView = 'status_board'" class="px-3 py-1 rounded-lg transition"
                        :class="activeView === 'status_board' ? 'bg-[#1C1917] text-white shadow-xs' : 'text-stone-600 hover:text-stone-900'">
                    ▥ Status
                </button>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="space-y-6">

            <!-- VIEW 1: Standard Cards (Now Displays Item Name inside Status Pill) -->
            <div x-show="activeView === 'standard'" class="space-y-8">
                <!-- Grouped View -->
                <template x-if="!hasActiveFilters">
                    <div class="space-y-8">
                        <template x-for="grp in displayGroups" :key="grp.title">
                            <div class="space-y-4">
                                <h3 class="font-serif font-bold text-xl text-stone-800 flex items-center gap-2">
                                    <span x-text="grp.title"></span>
                                    <span class="text-xs font-sans font-normal text-stone-400" x-text="`(${grp.members.length})`"></span>
                                </h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                    <template x-for="(person, idx) in grp.members" :key="person.id">
                                        <div class="bg-white rounded-app border border-[#ECE5D8] p-5 shadow-2xs hover:border-[#D8CFBF] transition flex flex-col justify-between">
                                            <div>
                                                <div class="flex justify-between items-start">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-xs"
                                                             :class="getAvatarColorClass(person.id || idx)">
                                                            <span x-text="person.full_name ? person.full_name.charAt(0).toUpperCase() : '?'"></span>
                                                        </div>
                                                        <div>
                                                            <h4 class="font-serif font-bold text-lg text-stone-900 leading-snug cursor-pointer hover:underline" 
                                                                @click="openPersonModal(person)" 
                                                                x-text="person.full_name"></h4>
                                                            <span class="text-xs text-stone-500 font-medium" x-text="person.relationship || 'Friend'"></span>
                                                        </div>
                                                    </div>
                                                    <button @click="openGiftModal({ person_id: person.id })" 
                                                            class="w-7 h-7 rounded-full bg-[#EAA23B] hover:bg-[#D9922C] text-white flex items-center justify-center font-bold text-sm shadow-2xs transition">
                                                        +
                                                    </button>
                                                </div>
                                                <div class="mt-3 flex items-center gap-1.5 text-xs text-stone-400 font-medium" x-show="person.birthdate">
                                                    <span>🎂</span>
                                                    <span x-text="formatDate(person.birthdate)"></span>
                                                </div>
                                                <div class="mt-3 flex flex-wrap gap-1.5">
                                                    <template x-for="gName in (person.group_names ? person.group_names.split(',') : ['General'])" :key="gName">
                                                        <span class="bg-[#F0EBE1] text-stone-600 text-[11px] font-medium px-2.5 py-0.5 rounded-full" x-text="gName.trim()"></span>
                                                    </template>
                                                </div>
                                            </div>

                                            <div class="mt-6 pt-3 border-t border-[#F5EFE6]">
                                                <div class="flex justify-between items-center text-xs text-stone-500 mb-2">
                                                    <span x-text="`${person.gifts ? person.gifts.length : 0} gifts · $${calculateTotalSpent(person)}`"></span>
                                                    <span class="text-stone-300 text-[10px]">▼</span>
                                                </div>
                                                <div class="flex flex-wrap gap-1.5" x-show="person.gifts && person.gifts.length > 0">
                                                    <template x-for="gift in person.gifts" :key="gift.id">
                                                        <button @click="openGiftModal(gift)"
                                                                class="px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1.5 transition"
                                                                :class="getStatusPillClass(gift.status)">
                                                            <span class="text-[8px]">●</span>
                                                            <span x-text="gift.item_name"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                                <div x-show="!person.gifts || person.gifts.length === 0" class="text-xs text-stone-400 italic">
                                                    No gifts added yet.
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Filtered Flat View -->
                <template x-if="hasActiveFilters">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <template x-for="(person, idx) in filteredPeople" :key="person.id">
                            <div class="bg-white rounded-app border border-[#ECE5D8] p-5 shadow-2xs hover:border-[#D8CFBF] transition flex flex-col justify-between">
                                <div>
                                    <div class="flex justify-between items-start">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-xs"
                                                 :class="getAvatarColorClass(person.id || idx)">
                                                <span x-text="person.full_name ? person.full_name.charAt(0).toUpperCase() : '?'"></span>
                                            </div>
                                            <div>
                                                <h4 class="font-serif font-bold text-lg text-stone-900 leading-snug cursor-pointer hover:underline" 
                                                    @click="openPersonModal(person)" 
                                                    x-text="person.full_name"></h4>
                                                <span class="text-xs text-stone-500 font-medium" x-text="person.relationship || 'Friend'"></span>
                                            </div>
                                        </div>
                                        <button @click="openGiftModal({ person_id: person.id })" 
                                                class="w-7 h-7 rounded-full bg-[#EAA23B] hover:bg-[#D9922C] text-white flex items-center justify-center font-bold text-sm shadow-2xs transition">
                                            +
                                        </button>
                                    </div>
                                    <div class="mt-3 flex items-center gap-1.5 text-xs text-stone-400 font-medium" x-show="person.birthdate">
                                        <span>🎂</span>
                                        <span x-text="formatDate(person.birthdate)"></span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        <template x-for="grp in (person.group_names ? person.group_names.split(',') : ['General'])" :key="grp">
                                            <span class="bg-[#F0EBE1] text-stone-600 text-[11px] font-medium px-2.5 py-0.5 rounded-full" x-text="grp.trim()"></span>
                                        </template>
                                    </div>
                                </div>

                                <div class="mt-6 pt-3 border-t border-[#F5EFE6]">
                                    <div class="flex justify-between items-center text-xs text-stone-500 mb-2">
                                        <span x-text="`${person.gifts ? person.gifts.length : 0} gifts · $${calculateTotalSpent(person)}`"></span>
                                        <span class="text-stone-300 text-[10px]">▼</span>
                                    </div>
                                    <div class="flex flex-wrap gap-1.5" x-show="person.gifts && person.gifts.length > 0">
                                        <template x-for="gift in person.gifts" :key="gift.id">
                                            <button @click="openGiftModal(gift)"
                                                    class="px-3 py-1 rounded-full text-xs font-medium inline-flex items-center gap-1.5 transition"
                                                    :class="getStatusPillClass(gift.status)">
                                                <span class="text-[8px]">●</span>
                                                <span x-text="gift.item_name"></span>
                                            </button>
                                        </template>
                                    </div>
                                    <div x-show="!person.gifts || person.gifts.length === 0" class="text-xs text-stone-400 italic">
                                        No gifts added yet.
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>
            </div>

            <!-- VIEW 2: Screenshot List Table View (Displays Gift Item Name inside Pill) -->
            <div x-show="activeView === 'compact'" class="space-y-6">
                <template x-if="!hasActiveFilters">
                    <div class="space-y-8">
                        <template x-for="grp in displayGroups" :key="grp.title">
                            <div class="space-y-3">
                                <h3 class="font-serif font-bold text-lg text-stone-800 flex items-center gap-2">
                                    <span x-text="grp.title"></span>
                                    <span class="text-xs font-sans font-normal text-stone-400" x-text="`(${grp.members.length})`"></span>
                                </h3>

                                <div class="bg-white rounded-app border border-[#ECE5D8] overflow-hidden shadow-2xs">
                                    <div class="bg-[#231C18] text-white px-6 py-3.5 flex justify-between items-center text-[11px] font-bold tracking-wider uppercase">
                                        <div class="w-1/3">NAME</div>
                                        <div class="w-1/2">GIFT</div>
                                        <div class="w-1/6 text-right"></div>
                                    </div>

                                    <div class="divide-y divide-[#F2EDE4]">
                                        <template x-for="(person, idx) in grp.members" :key="person.id">
                                            <div class="px-6 py-4 flex items-center justify-between hover:bg-[#FAF7F2]/60 transition">
                                                <!-- Col 1: Avatar + Name -->
                                                <div class="w-1/3 flex items-center gap-3">
                                                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-xs shrink-0"
                                                         :class="getAvatarColorClass(person.id || idx)">
                                                        <span x-text="person.full_name ? person.full_name.charAt(0).toUpperCase() : '?'"></span>
                                                    </div>
                                                    <div class="flex items-center gap-1.5 truncate">
                                                        <span class="font-serif font-bold text-base text-stone-900 cursor-pointer hover:underline truncate"
                                                              @click="openPersonModal(person)"
                                                              x-text="person.full_name"></span>
                                                        <span x-show="person.is_abroad == 1" class="text-xs">✈️</span>
                                                    </div>
                                                </div>

                                                <!-- Col 2: Gift Pills with Item Name -->
                                                <div class="w-1/2 flex flex-wrap gap-2 items-center">
                                                    <template x-for="gift in person.gifts" :key="gift.id">
                                                        <button @click="openGiftModal(gift)"
                                                                class="px-4 py-1.5 rounded-full text-xs font-medium inline-flex items-center gap-1.5 transition"
                                                                :class="getStatusPillClass(gift.status)">
                                                            <span class="text-[9px]">●</span>
                                                            <span x-text="gift.item_name"></span>
                                                        </button>
                                                    </template>
                                                    <span x-show="!person.gifts || person.gifts.length === 0" class="text-xs text-stone-400 font-medium">
                                                        No gifts yet
                                                    </span>
                                                </div>

                                                <!-- Col 3: Quick Add Gift -->
                                                <div class="w-1/6 text-right">
                                                    <button @click="openGiftModal({ person_id: person.id })" 
                                                            class="w-7 h-7 rounded-full bg-[#EAA23B] hover:bg-[#D9922C] text-white inline-flex items-center justify-center font-bold text-sm shadow-2xs transition">
                                                        +
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <!-- Filtered Flat View -->
                <template x-if="hasActiveFilters">
                    <div class="bg-white rounded-app border border-[#ECE5D8] overflow-hidden shadow-2xs">
                        <div class="bg-[#231C18] text-white px-6 py-3.5 flex justify-between items-center text-[11px] font-bold tracking-wider uppercase">
                            <div class="w-1/3">NAME</div>
                            <div class="w-1/2">GIFT</div>
                            <div class="w-1/6 text-right"></div>
                        </div>

                        <div class="divide-y divide-[#F2EDE4]">
                            <template x-for="(person, idx) in filteredPeople" :key="person.id">
                                <div class="px-6 py-4 flex items-center justify-between hover:bg-[#FAF7F2]/60 transition">
                                    <div class="w-1/3 flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-full flex items-center justify-center text-white font-bold text-sm shadow-xs shrink-0"
                                             :class="getAvatarColorClass(person.id || idx)">
                                            <span x-text="person.full_name ? person.full_name.charAt(0).toUpperCase() : '?'"></span>
                                        </div>
                                        <div class="flex items-center gap-1.5 truncate">
                                            <span class="font-serif font-bold text-base text-stone-900 cursor-pointer hover:underline truncate"
                                                  @click="openPersonModal(person)"
                                                  x-text="person.full_name"></span>
                                            <span x-show="person.is_abroad == 1" class="text-xs">✈️</span>
                                        </div>
                                    </div>

                                    <div class="w-1/2 flex flex-wrap gap-2 items-center">
                                        <template x-for="gift in person.gifts" :key="gift.id">
                                            <button @click="openGiftModal(gift)"
                                                    class="px-4 py-1.5 rounded-full text-xs font-medium inline-flex items-center gap-1.5 transition"
                                                    :class="getStatusPillClass(gift.status)">
                                                <span class="text-[9px]">●</span>
                                                <span x-text="gift.item_name"></span>
                                            </button>
                                        </template>
                                        <span x-show="!person.gifts || person.gifts.length === 0" class="text-xs text-stone-400 font-medium">
                                            No gifts yet
                                        </span>
                                    </div>

                                    <div class="w-1/6 text-right">
                                        <button @click="openGiftModal({ person_id: person.id })" 
                                                class="w-7 h-7 rounded-full bg-[#EAA23B] hover:bg-[#D9922C] text-white inline-flex items-center justify-center font-bold text-sm shadow-2xs transition">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- VIEW 3: Status Kanban Board -->
            <div x-show="activeView === 'status_board'" class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <template x-for="col in ['Idea', 'Purchased', 'Wrapped', 'Given']" :key="col">
                    <div class="bg-white rounded-app border border-[#ECE5D8] p-4 shadow-2xs">
                        <h4 class="font-serif font-bold text-sm text-stone-900 mb-3 flex justify-between items-center">
                            <span x-text="col"></span>
                            <span class="text-xs font-sans px-2 py-0.5 bg-stone-100 rounded-full text-stone-600" x-text="getGiftsByStatus(col).length"></span>
                        </h4>
                        <div class="space-y-2.5">
                            <template x-for="item in getGiftsByStatus(col)" :key="item.id">
                                <div class="border border-[#ECE5D8] bg-[#FAF7F2] rounded-xl p-3 text-xs shadow-2xs">
                                    <div class="font-semibold text-stone-900" x-text="item.item_name"></div>
                                    <div class="text-stone-500 text-[11px] mt-0.5">👤 <span x-text="item.recipient_name"></span></div>
                                    <div class="mt-2 flex justify-between items-center text-[10px]">
                                        <span class="font-serif text-[#EAA23B] font-bold" x-text="'$' + (item.price || 0)"></span>
                                        <button @click="openGiftModal(item)" class="text-stone-500 hover:text-stone-900 underline">Edit</button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Empty States -->
            <div x-show="people.length === 0" class="text-stone-400 italic text-center py-12">
                No people tracked yet. Click "+ Person" to get started!
            </div>
            <div x-show="people.length > 0 && filteredPeople.length === 0" class="text-stone-400 italic text-center py-12">
                No recipients matching your current filters.
            </div>

        </main>
    </div>

    <!-- Modal: Add / Edit Person -->
    <div x-show="showPersonModal" 
         @keydown.escape.window="showPersonModal = false"
         class="fixed inset-0 bg-[#231C18]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
         style="display: none;">
        <div class="bg-white rounded-app max-w-md w-full p-6 space-y-4 shadow-xl border border-[#ECE5D8]" @click.away="showPersonModal = false">
            <h3 class="font-serif font-bold text-xl text-stone-900" x-text="currentPerson.id ? 'Edit Person' : 'Add Person'"></h3>
            <div class="space-y-3">
                <input type="text" x-model="currentPerson.full_name" placeholder="Full Name *" 
                       class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-[#EAA23B] focus:outline-none">
                
                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1">Relationship</label>
                    <select x-model="currentPerson.relationship" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm bg-white text-stone-800">
                        <template x-for="rel in availableRelationships" :key="rel">
                            <option :value="rel" x-text="rel"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1">Groups</label>
                    <div class="flex flex-wrap gap-1.5 mb-2 max-h-24 overflow-y-auto border border-[#ECE5D8] p-2 rounded-xl bg-stone-50/50">
                        <template x-for="grp in existingGroups" :key="grp.id">
                            <button type="button" @click="toggleGroup(grp.name)" 
                                    class="px-2.5 py-1 text-xs rounded-full border transition"
                                    :class="selectedGroups.includes(grp.name) ? 'bg-[#1C1917] text-white border-transparent' : 'bg-white text-stone-600 border-[#DFD8CC]'"
                                    x-text="grp.name">
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-stone-500 mb-1">Birthday</label>
                    <input type="date" x-model="currentPerson.birthdate" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm bg-white text-stone-800">
                </div>

                <div class="p-2.5 bg-stone-50 border border-stone-200 rounded-xl flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-stone-800">Abroad</span>
                        <p class="text-[11px] text-stone-500">Lives overseas (shows airplane icon)</p>
                    </div>
                    <input type="checkbox" x-model="currentPerson.is_abroad" class="rounded text-[#EAA23B] w-4 h-4">
                </div>

                <textarea x-model="currentPerson.notes" placeholder="Notes (interests, clothing sizes...)" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button @click="showPersonModal = false" class="px-4 py-2 text-sm font-semibold text-stone-600 hover:text-stone-800">Cancel</button>
                <button @click="savePerson()" class="px-5 py-2 bg-[#E66A55] hover:bg-[#D75944] text-white rounded-full text-sm font-semibold shadow-xs">Save</button>
            </div>
        </div>
    </div>

    <!-- Modal: Add / Edit Gift -->
    <div x-show="showGiftModal" 
         @keydown.escape.window="showGiftModal = false"
         class="fixed inset-0 bg-[#231C18]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
         style="display: none;">
        <div class="bg-white rounded-app max-w-md w-full p-6 space-y-4 shadow-xl border border-[#ECE5D8]" @click.away="showGiftModal = false">
            <h3 class="font-serif font-bold text-xl text-stone-900" x-text="currentGift.id ? 'Edit Gift' : 'Add Gift'"></h3>
            <div class="space-y-3">
                <input type="text" x-model="currentGift.item_name" placeholder="Gift Name *" 
                       class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm focus:ring-2 focus:ring-[#EAA23B] focus:outline-none">
                
                <div>
                    <label class="block text-[11px] font-semibold text-stone-500 mb-0.5">Recipient</label>
                    <select x-model="currentGift.person_id" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm bg-white text-stone-800">
                        <option value="">📦 -- Unassigned Stash --</option>
                        <template x-for="p in people" :key="p.id">
                            <option :value="p.id" x-text="p.full_name"></option>
                        </template>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-stone-500 mb-0.5">Event</label>
                        <select x-model="currentGift.event_id" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm bg-white text-stone-800">
                            <option value="">General</option>
                            <template x-for="e in events" :key="e.id">
                                <option :value="e.id" x-text="e.title"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-stone-500 mb-0.5">Status</label>
                        <select x-model="currentGift.status" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm bg-white text-stone-800">
                            <option value="Idea">Idea</option>
                            <option value="Purchased">Purchased</option>
                            <option value="Wrapped">Wrapped</option>
                            <option value="Given">Given</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] font-semibold text-stone-500 mb-0.5">Price ($)</label>
                        <input type="number" step="0.01" x-model="currentGift.price" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-[11px] font-semibold text-stone-500 mb-0.5">Storage Bin</label>
                        <input type="text" x-model="currentGift.storage_location" placeholder="e.g. Closet 2" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm">
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center pt-2">
                <div>
                    <button type="button" 
                            x-show="currentGift.id" 
                            @click="deleteGift(currentGift.id); showGiftModal = false;" 
                            class="text-xs font-semibold text-red-600 hover:text-red-800 hover:underline">
                        Delete Gift
                    </button>
                </div>
                
                <div class="flex gap-2">
                    <button @click="showGiftModal = false" class="px-4 py-2 text-sm font-semibold text-stone-600 hover:text-stone-800">Cancel</button>
                    <button @click="saveGift()" class="px-5 py-2 bg-[#EAA23B] hover:bg-[#D9922C] text-white rounded-full text-sm font-semibold shadow-xs">Save Gift</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add Event -->
    <div x-show="showEventModal" 
         @keydown.escape.window="showEventModal = false"
         class="fixed inset-0 bg-[#231C18]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
         style="display: none;">
        <div class="bg-white rounded-app max-w-md w-full p-6 space-y-4 shadow-xl border border-[#ECE5D8]" @click.away="showEventModal = false">
            <h3 class="font-serif font-bold text-xl text-stone-900">Add Target Event</h3>
            <div class="space-y-3">
                <input type="text" x-model="newEvent.title" placeholder="Event Title (e.g. Christmas 2026)" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm">
                <input type="date" x-model="newEvent.event_date" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm">
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button @click="showEventModal = false" class="px-4 py-2 text-sm font-semibold text-stone-600 hover:text-stone-800">Cancel</button>
                <button @click="saveEvent()" class="px-5 py-2 bg-[#1C1917] text-white rounded-full text-sm font-semibold shadow-xs">Save Event</button>
            </div>
        </div>
    </div>

    <!-- Modal: CSV Import -->
    <div x-show="showImportModal" 
         @keydown.escape.window="showImportModal = false"
         class="fixed inset-0 bg-[#231C18]/50 backdrop-blur-xs flex items-center justify-center p-4 z-50" 
         style="display: none;">
        <div class="bg-white rounded-app max-w-md w-full p-6 space-y-4 shadow-xl border border-[#ECE5D8]" @click.away="showImportModal = false">
            <h3 class="font-serif font-bold text-xl text-stone-900">Import CSV</h3>
            <input type="file" id="csvFileInput" accept=".csv" class="w-full border border-[#DFD8CC] rounded-xl px-3 py-2 text-sm">
            <div class="flex justify-end gap-2 pt-2">
                <button @click="showImportModal = false" class="px-4 py-2 text-sm font-semibold text-stone-600 hover:text-stone-800">Cancel</button>
                <button @click="uploadCSV()" class="px-5 py-2 bg-[#E66A55] text-white rounded-full text-sm font-semibold shadow-xs">Upload</button>
            </div>
        </div>
    </div>

    <!-- Floating Scroll to Top Button -->
    <div x-data="{ showTopBtn: false }" 
         @scroll.window="showTopBtn = (window.pageYOffset > 300)" 
         class="fixed bottom-6 right-6 z-50">
        <button type="button"
                x-show="showTopBtn"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-3"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-3"
                @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
                class="w-11 h-11 rounded-full bg-[#1C1917] hover:bg-[#2E2824] text-white flex items-center justify-center shadow-lg border border-[#3E342F] cursor-pointer transition focus:outline-none"
                title="Scroll to top"
                style="display: none;">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <script>
        function giftApp() {
            return {
                selectedYear: 2026,
                activeView: 'compact',
                people: [],
                events: [],
                existingGroups: [],
                selectedGroups: ['General'],
                search: '',
                filters: {
                    groups: '',
                    relationships: '',
                    status: ''
                },
                showEventModal: false,
                showPersonModal: false,
                showGiftModal: false,
                showImportModal: false,

                newEvent: { title: '', event_date: '', event_year: 2026 },
                currentPerson: { id: null, full_name: '', relationship: 'Friend', birthdate: '', is_abroad: false, notes: '' },
                currentGift: { id: null, item_name: '', person_id: '', event_id: '', status: 'Idea', price: '', storage_location: '', year_given: 2026 },

                getAvatarColorClass(key) {
                    const colors = ['bg-[#E66A55]', 'bg-[#6B5187]', 'bg-[#5FA593]', 'bg-[#7C5991]', 'bg-[#3D7489]', 'bg-[#D97706]'];
                    const num = typeof key === 'number' ? key : (key && key.charCodeAt ? key.charCodeAt(0) : 0);
                    return colors[num % colors.length];
                },

                getStatusPillClass(status) {
                    if (status === 'Idea') return 'status-pill-idea';
                    if (status === 'Purchased') return 'status-pill-purchased';
                    if (status === 'Wrapped') return 'status-pill-wrapped';
                    if (status === 'Given') return 'status-pill-given';
                    return 'bg-stone-100 text-stone-700';
                },

                get daysUntilChristmas() {
                    const today = new Date();
                    const targetYear = this.selectedYear || today.getFullYear();
                    const xDate = new Date(targetYear, 11, 25);
                    const diffTime = xDate - today;
                    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                    return diffDays > 0 ? diffDays : 0;
                },

                get christmasStats() {
                    let assigned = 0;
                    let sorted = 0;

                    const christmasEventIds = this.events
                        .filter(e => e.title && e.title.toLowerCase().includes('christmas'))
                        .map(e => e.id);

                    this.people.forEach(p => {
                        if (p.gifts) {
                            p.gifts.forEach(g => {
                                const isChristmasGift = (!g.event_id && christmasEventIds.length === 0) || 
                                                        (g.event_id && christmasEventIds.includes(Number(g.event_id))) ||
                                                        (g.event_title && g.event_title.toLowerCase().includes('christmas'));

                                if (isChristmasGift) {
                                    assigned++;
                                    if (g.status === 'Purchased' || g.status === 'Wrapped' || g.status === 'Given') {
                                        sorted++;
                                    }
                                }
                            });
                        }
                    });

                    const totalTarget = assigned > 0 ? assigned : this.people.length;
                    const percent = totalTarget > 0 ? Math.round((sorted / totalTarget) * 100) : 0;

                    return { assigned, sorted, percent, totalTarget };
                },

                get upcomingBirthdays() {
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    const results = [];

                    this.people.forEach(p => {
                        if (!p.birthdate) return;
                        const bParts = p.birthdate.split('-');
                        if (bParts.length < 3) return;

                        const bMonth = parseInt(bParts[1], 10) - 1;
                        const bDay = parseInt(bParts[2], 10);
                        const bYear = parseInt(bParts[0], 10);

                        let nextBday = new Date(today.getFullYear(), bMonth, bDay);
                        if (nextBday < today) {
                            nextBday = new Date(today.getFullYear() + 1, bMonth, bDay);
                        }

                        const diffDays = Math.ceil((nextBday - today) / (1000 * 60 * 60 * 24));

                        if (diffDays >= 0 && diffDays <= 92) {
                            const turningAge = bYear > 1900 ? nextBday.getFullYear() - bYear : null;
                            const formattedDate = nextBday.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' });

                            results.push({
                                person: p,
                                daysUntil: diffDays,
                                formattedDate,
                                turningAge
                            });
                        }
                    });

                    return results.sort((a, b) => a.daysUntil - b.daysUntil);
                },

                get availableRelationships() {
                    return ['Partner', 'Spouse', 'Family', 'Mother', 'Father', 'Child', 'Sibling', 'Friend', 'Coworker'];
                },

                get hasActiveFilters() {
                    return !!(this.filters.groups || this.filters.relationships || this.filters.status || this.search.trim());
                },

                resetFilters() {
                    this.filters = { groups: '', relationships: '', status: '' };
                    this.search = '';
                },

                calculateTotalSpent(person) {
                    if (!person.gifts) return 0;
                    return person.gifts.reduce((sum, g) => sum + (parseFloat(g.price) || 0), 0).toFixed(0);
                },

                formatDate(dateStr) {
                    if (!dateStr) return '';
                    const d = new Date(dateStr);
                    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
                },

                get filteredPeople() {
                    return this.people.filter(p => {
                        if (this.search.trim()) {
                            const q = this.search.toLowerCase().trim();
                            const nameMatch = p.full_name && p.full_name.toLowerCase().includes(q);
                            const giftMatch = p.gifts && p.gifts.some(g => g.item_name && g.item_name.toLowerCase().includes(q));
                            if (!nameMatch && !giftMatch) return false;
                        }

                        if (this.filters.groups) {
                            const pGroups = p.group_names ? p.group_names.split(',').map(s => s.trim()) : ['General'];
                            if (!pGroups.includes(this.filters.groups)) return false;
                        }

                        if (this.filters.relationships) {
                            if ((p.relationship || 'Friend') !== this.filters.relationships) return false;
                        }

                        if (this.filters.status) {
                            if (this.filters.status === 'no_gifts') {
                                if (p.gifts && p.gifts.length > 0) return false;
                            } else {
                                if (!p.gifts || !p.gifts.some(g => g.status === this.filters.status)) return false;
                            }
                        }

                        return true;
                    });
                },

                get displayGroups() {
                    const groupMap = {};
                    this.filteredPeople.forEach(person => {
                        const groups = person.group_names && person.group_names.trim() !== ''
                            ? person.group_names.split(',').map(s => s.trim())
                            : ['General'];

                        groups.forEach(grp => {
                            if (!groupMap[grp]) groupMap[grp] = [];
                            groupMap[grp].push(person);
                        });
                    });

                    return Object.keys(groupMap).sort().map(name => ({
                        title: name,
                        members: groupMap[name]
                    }));
                },

                getGiftsByStatus(status) {
                    const list = [];
                    this.people.forEach(p => {
                        if (p.gifts) {
                            p.gifts.filter(g => g.status === status).forEach(g => {
                                list.push({ ...g, recipient_name: p.full_name });
                            });
                        }
                    });
                    return list;
                },

                changeYear(year) {
                    this.selectedYear = year;
                    this.fetchData();
                },

                fetchData() {
                    fetch(`api.php?action=get_dashboard&year=${this.selectedYear}`)
                        .then(res => res.json())
                        .then(data => {
                            this.people = data.people || [];
                            this.events = data.events || [];
                            this.existingGroups = data.groups || [];
                        });
                },

                openPersonModal(person = null) {
                    if (person) {
                        this.currentPerson = { 
                            id: person.id,
                            full_name: person.full_name,
                            relationship: person.relationship || 'Friend',
                            birthdate: person.birthdate || '',
                            is_abroad: person.is_abroad == 1,
                            notes: person.notes || ''
                        };
                        this.selectedGroups = person.group_names ? person.group_names.split(',').map(s => s.trim()) : ['General'];
                    } else {
                        this.currentPerson = { id: null, full_name: '', relationship: 'Friend', birthdate: '', is_abroad: false, notes: '' };
                        this.selectedGroups = ['General'];
                    }
                    this.showPersonModal = true;
                },

                toggleGroup(name) {
                    if (this.selectedGroups.includes(name)) {
                        this.selectedGroups = this.selectedGroups.filter(g => g !== name);
                    } else {
                        this.selectedGroups.push(name);
                    }
                },

                savePerson() {
                    const fd = new FormData();
                    if (this.currentPerson.id) fd.append('id', this.currentPerson.id);
                    fd.append('full_name', this.currentPerson.full_name);
                    fd.append('relationship', this.currentPerson.relationship);
                    fd.append('birthdate', this.currentPerson.birthdate || '');
                    fd.append('is_abroad', this.currentPerson.is_abroad ? '1' : '0');
                    fd.append('notes', this.currentPerson.notes || '');
                    fd.append('groups', JSON.stringify(this.selectedGroups));

                    fetch('api.php?action=save_person', { method: 'POST', body: fd })
                        .then(() => {
                            this.showPersonModal = false;
                            this.fetchData();
                        });
                },

                openGiftModal(gift = null) {
                    if (gift && gift.id) {
                        this.currentGift = { ...gift };
                    } else if (gift && gift.person_id) {
                        this.currentGift = { id: null, item_name: '', person_id: gift.person_id, event_id: '', status: 'Idea', price: '', storage_location: '', year_given: this.selectedYear };
                    } else {
                        this.currentGift = { id: null, item_name: '', person_id: '', event_id: '', status: 'Idea', price: '', storage_location: '', year_given: this.selectedYear };
                    }
                    this.showGiftModal = true;
                },

                saveGift() {
                    if (!this.currentGift.item_name || !this.currentGift.item_name.trim()) {
                        alert('Please enter a gift name.');
                        return;
                    }

                    const fd = new FormData();
                    if (this.currentGift.id) fd.append('id', this.currentGift.id);
                    fd.append('item_name', this.currentGift.item_name.trim());
                    fd.append('person_id', this.currentGift.person_id || '');
                    fd.append('event_id', this.currentGift.event_id || '');
                    fd.append('status', this.currentGift.status || 'Idea');
                    fd.append('price', this.currentGift.price !== '' ? this.currentGift.price : '0');
                    fd.append('storage_location', this.currentGift.storage_location || '');
                    fd.append('year_given', this.currentGift.year_given || this.selectedYear);

                    fetch('api.php?action=save_gift', { method: 'POST', body: fd })
                        .then(async res => {
                            const data = await res.json();
                            if (!res.ok) {
                                throw new Error(data.error || 'Server error occurred');
                            }
                            return data;
                        })
                        .then(() => {
                            this.showGiftModal = false;
                            this.fetchData();
                        })
                        .catch(err => {
                            alert('Failed to save gift: ' + err.message);
                        });
                },

                deleteGift(id) {
                    if (!confirm('Are you sure you want to delete this gift?')) return;
                    const fd = new FormData();
                    fd.append('id', id);
                    fetch('api.php?action=delete_gift', { method: 'POST', body: fd })
                        .then(() => this.fetchData());
                },

                openEventModal() {
                    this.newEvent = { title: '', event_date: '', event_year: this.selectedYear };
                    this.showEventModal = true;
                },

                saveEvent() {
                    const fd = new FormData();
                    fd.append('title', this.newEvent.title);
                    fd.append('event_date', this.newEvent.event_date);
                    fd.append('event_year', this.newEvent.event_year);

                    fetch('api.php?action=add_event', { method: 'POST', body: fd })
                        .then(() => {
                            this.showEventModal = false;
                            this.fetchData();
                        });
                },

                uploadCSV() {
                    const fileInput = document.getElementById('csvFileInput');
                    if (!fileInput.files.length) return alert('Select a CSV file first.');
                    const fd = new FormData();
                    fd.append('csv_file', fileInput.files[0]);

                    fetch('api.php?action=import_csv', { method: 'POST', body: fd })
                        .then(res => res.json())
                        .then(data => {
                            alert(`Imported ${data.imported || 0} records!`);
                            this.showImportModal = false;
                            fileInput.value = '';
                            this.fetchData();
                        });
                }
            }
        }
    </script>
</body>
</html>