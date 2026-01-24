<template>
    <div>
        <p class="text-muted small mb-3">
            To Add, Type the Name into 'Search/Add Text Box' & Click Button, Next to 'Add New Box'<br>
            To Edit, Click on Name & Press INSERT Key.<br>
            To Delete, Click on Name & Press DELETE Key.
        </p>

        <div class="row mb-3">
            <div class="col-md-4">
                <label class="form-label">Language</label>
                <select v-model="selectedLanguage" class="form-select">
                    <option value="english">English</option>
                    <option value="marathi">Marathi</option>
                    <option value="hindi">Hindi</option>
                </select>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-8">
                <label class="form-label">Search/Add :</label>
                <div class="input-group">
                    <input
                        type="text"
                        class="form-control"
                        v-model="searchText"
                        placeholder="Type dosage instruction"
                        @keyup.enter="addDose"
                    />
                    <button class="btn btn-primary" @click="addDose">
                        Add New
                    </button>
                </div>
            </div>
        </div>

        <!-- Dose List -->
        <div class="border rounded p-3" style="height: 450px; overflow-y: auto;">
            <div
                v-for="dose in filteredDoses"
                :key="dose.id"
                class="dose-item py-1 px-2 mb-1 cursor-pointer"
                :class="{ 'bg-primary text-white': selectedDose?.id === dose.id }"
                @click="selectDose(dose)"
                @dblclick="editDose(dose)"
            >
                {{ dose.text }}
            </div>
            <div v-if="filteredDoses.length === 0" class="text-center text-muted py-4">
                No dosage instructions found
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const selectedLanguage = ref('english');
const searchText = ref('');
const selectedDose = ref(null);

const doses = ref([
    { id: 1, text: '0.5 ML', language: 'english' },
    { id: 2, text: '1 time in the morning & 1 time in the evening', language: 'english' },
    { id: 3, text: '2TIMES', language: 'english' },
    { id: 4, text: 'ONE TIME', language: 'english' },
    { id: 5, text: 'test', language: 'english' },
    { id: 6, text: 'test45', language: 'english' },
    { id: 7, text: '?????? mix', language: 'marathi' },
    { id: 8, text: '????? ? ??? ?????? ?? ??????', language: 'marathi' },
    { id: 9, text: '0 - 0 - 1', language: 'marathi' },
    { id: 10, text: '0 - 0 - 1 night', language: 'marathi' },
    { id: 11, text: '0 - 0 - 1 alternate night', language: 'marathi' },
    { id: 12, text: '0 - 0 - 1 teaspoon', language: 'marathi' },
    { id: 13, text: '0 - 0 - 1 teaspoon===', language: 'marathi' },
    { id: 14, text: '0 - 0 - 1/2', language: 'marathi' },
    { id: 15, text: '0 - 1 - 0', language: 'marathi' }
]);

const filteredDoses = computed(() => {
    let filtered = doses.value.filter(d => d.language === selectedLanguage.value);

    if (searchText.value.trim()) {
        const search = searchText.value.toLowerCase();
        filtered = filtered.filter(d =>
            d.text.toLowerCase().includes(search)
        );
    }

    return filtered;
});

const selectDose = (dose) => {
    selectedDose.value = dose;
};

const editDose = (dose) => {
    searchText.value = dose.text;
    selectedDose.value = dose;
};

const addDose = () => {
    if (searchText.value.trim()) {
        const newDose = {
            id: Math.max(...doses.value.map(d => d.id), 0) + 1,
            text: searchText.value.trim(),
            language: selectedLanguage.value
        };
        doses.value.push(newDose);
        searchText.value = '';
        selectedDose.value = newDose;
    }
};
</script>

<style scoped>
.dose-item {
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.dose-item:hover:not(.bg-primary) {
    background-color: rgba(0, 0, 0, 0.05);
}

.cursor-pointer {
    cursor: pointer;
}
</style>
