<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const joinForm = useForm({
    commander_name: '',
    race_id: '',
    description: '',
});

const leaveForm = useForm({
    note: '',
});

const submitJoin = () => {
    joinForm.post(route('enrollment.join'));
};

const submitLeave = () => {
    leaveForm.post(route('enrollment.leave'));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2
                class="text-xl font-semibold leading-tight text-gray-800"
            >
                Dashboard
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div
                    class="overflow-hidden bg-white shadow-sm sm:rounded-lg"
                >
                    <div class="p-6 text-gray-900">
                        You're logged in!
                        <div v-if="$page.props.flash && $page.props.flash.status" class="mt-3 rounded border border-green-600 bg-green-50 p-2 text-green-800">
                            {{$page.props.flash.status}}
                        </div>
                        
                        <!-- Player Enrollment Widgets -->
                        <div class="mt-6 space-y-6">
                            <!-- Pending notices -->
                            <div v-if="$page.props.player && $page.props.player.pendingJoin" class="rounded border border-blue-600 bg-blue-50 p-3 text-blue-800">
                                Votre demande d'inscription est en attente. Elle sera traitée lors de la résolution du prochain tour.
                            </div>
                            <div v-if="$page.props.player && $page.props.player.pendingLeave" class="rounded border border-blue-600 bg-blue-50 p-3 text-blue-800">
                                Votre demande de départ est en attente. Elle sera traitée lors de la résolution du prochain tour.
                            </div>

                            <!-- Join form -->
                            <div v-if="$page.props.player && !$page.props.player.hasCommander && !$page.props.player.pendingJoin">
                                <div v-if="$page.props.player.freezeWindowActive" class="mb-2 rounded border border-amber-600 bg-amber-50 p-2 text-amber-800">
                                    Les inscriptions sont gelées avant la résolution du tour.
                                </div>
                                <div v-else-if="!$page.props.player.registrationOpen" class="mb-2 rounded border border-gray-400 bg-gray-50 p-2 text-gray-700">
                                    Les inscriptions sont fermées.
                                </div>
                                <form @submit.prevent="submitJoin" class="grid gap-3">
                                    <h3 class="text-lg font-semibold">Rejoindre la partie</h3>
                                    <div>
                                        <label class="block text-sm">Nom du commandant</label>
                                        <input v-model="joinForm.commander_name" type="text" class="w-full rounded border p-2" required>
                                        <div v-if="joinForm.errors.commander_name" class="text-sm text-red-700 mt-1">{{ joinForm.errors.commander_name }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm">Race (ID facultatif)</label>
                                        <input v-model="joinForm.race_id" type="number" min="1" class="w-full rounded border p-2" placeholder="1">
                                        <div v-if="joinForm.errors.race_id" class="text-sm text-red-700 mt-1">{{ joinForm.errors.race_id }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm">Description (facultatif)</label>
                                        <textarea v-model="joinForm.description" class="w-full rounded border p-2" rows="2"></textarea>
                                        <div v-if="joinForm.errors.description" class="text-sm text-red-700 mt-1">{{ joinForm.errors.description }}</div>
                                    </div>
                                    <div>
                                        <button :disabled="$page.props.player.freezeWindowActive || !$page.props.player.registrationOpen || joinForm.processing" class="inline-flex items-center rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700 disabled:opacity-60">
                                            Demander à rejoindre
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Leave form -->
                            <div v-if="$page.props.player && $page.props.player.hasCommander && !$page.props.player.pendingLeave">
                                <div v-if="$page.props.player.freezeWindowActive" class="mb-2 rounded border border-amber-600 bg-amber-50 p-2 text-amber-800">
                                    Les départs sont gelés avant la résolution du tour.
                                </div>
                                <form @submit.prevent="submitLeave" class="grid gap-3">
                                    <h3 class="text-lg font-semibold">Quitter la partie</h3>
                                    <p class="text-sm text-gray-700">Vos actifs seront transférés selon la politique: commandant neutre par défaut.</p>
                                    <div>
                                        <label class="block text-sm">Note (facultatif)</label>
                                        <textarea v-model="leaveForm.note" class="w-full rounded border p-2" rows="2"></textarea>
                                        <div v-if="leaveForm.errors.note" class="text-sm text-red-700 mt-1">{{ leaveForm.errors.note }}</div>
                                    </div>
                                    <div>
                                        <button :disabled="$page.props.player.freezeWindowActive || leaveForm.processing" class="inline-flex items-center rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700 disabled:opacity-60">
                                            Demander à quitter
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <div class="mt-4">
                            <a :href="route('game.dashboard')" class="inline-flex items-center rounded bg-emerald-600 px-4 py-2 text-white hover:bg-emerald-700">
                                Accéder au jeu
                            </a>
                        </div>
                        <div v-if="$page.props.can && $page.props.can.admin" class="mt-4">
                            <a :href="route('admin.dashboard')" class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="mr-2 h-5 w-5"><path d="M9.5 2a.5.5 0 01.5.5V4h1V2.5a.5.5 0 011 0V4h.5A2.5 2.5 0 0115 6.5V8h1.5a.5.5 0 010 1H15v1h1.5a.5.5 0 010 1H15v1.5A2.5 2.5 0 0112.5 15H12v1.5a.5.5 0 01-1 0V15h-1v1.5a.5.5 0 01-1 0V15h-.5A2.5 2.5 0 016 12.5V11H4.5a.5.5 0 010-1H6V9H4.5a.5.5 0 010-1H6V6.5A2.5 2.5 0 018.5 4H9V2.5a.5.5 0 01.5-.5zM8.5 5A1.5 1.5 0 007 6.5V13a1.5 1.5 0 001.5 1.5h5A1.5 1.5 0 0015 13V6.5A1.5 1.5 0 0013.5 5h-5z"/></svg>
                                Go to Administration
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
