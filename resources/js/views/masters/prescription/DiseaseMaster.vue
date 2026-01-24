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
                        placeholder="Type disease name"
                        @keyup.enter="addDisease"
                    />
                    <button class="btn btn-primary" @click="addDisease">
                        Add New
                    </button>
                </div>
            </div>
        </div>

        <!-- Disease List -->
        <div class="border rounded p-3" style="height: 450px; overflow-y: auto;">
            <div
                v-for="disease in filteredDiseases"
                :key="disease.id"
                class="disease-item py-1 px-2 mb-1 cursor-pointer"
                :class="{ 'bg-primary text-white': selectedDisease?.id === disease.id }"
                @click="selectDisease(disease)"
                @dblclick="editDisease(disease)"
            >
                {{ disease.name }}
            </div>
            <div v-if="filteredDiseases.length === 0" class="text-center text-muted py-4">
                No diseases found
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const selectedLanguage = ref('english');
const searchText = ref('');
const selectedDisease = ref(null);

const diseases = ref([
    { id: 1, name: 'Acne', language: 'english' },
    { id: 2, name: 'Allergic Rhinitis', language: 'english' },
    { id: 3, name: 'Asthma', language: 'english' },
    { id: 4, name: 'Back Pain', language: 'english' },
    { id: 5, name: 'Bronchitis', language: 'english' },
    { id: 6, name: 'Common Cold', language: 'english' },
    { id: 7, name: 'Conjunctivitis', language: 'english' },
    { id: 8, name: 'Constipation', language: 'english' },
    { id: 9, name: 'Cough', language: 'english' },
    { id: 10, name: 'Dengue Fever', language: 'english' },
    { id: 11, name: 'Diabetes Mellitus', language: 'english' },
    { id: 12, name: 'Diarrhea', language: 'english' },
    { id: 13, name: 'Eczema', language: 'english' },
    { id: 14, name: 'Fever', language: 'english' },
    { id: 15, name: 'Gastritis', language: 'english' },
    { id: 16, name: 'Headache', language: 'english' },
    { id: 17, name: 'Hypertension', language: 'english' },
    { id: 18, name: 'Influenza', language: 'english' },
    { id: 19, name: 'Malaria', language: 'english' },
    { id: 20, name: 'Migraine', language: 'english' },
    { id: 21, name: 'Pneumonia', language: 'english' },
    { id: 22, name: 'Skin Infection', language: 'english' },
    { id: 23, name: 'Tonsillitis', language: 'english' },
    { id: 24, name: 'Typhoid', language: 'english' },
    { id: 25, name: 'Urinary Tract Infection', language: 'english' },
    { id: 26, name: 'त्वचा संक्रमण', language: 'marathi' },
    { id: 27, name: 'ताप', language: 'marathi' },
    { id: 28, name: 'डोकेदुखी', language: 'marathi' },
    { id: 29, name: 'खोकला', language: 'marathi' },
    { id: 30, name: 'मधुमेह', language: 'marathi' }
]);

const filteredDiseases = computed(() => {
    let filtered = diseases.value.filter(d => d.language === selectedLanguage.value);

    if (searchText.value.trim()) {
        const search = searchText.value.toLowerCase();
        filtered = filtered.filter(d =>
            d.name.toLowerCase().includes(search)
        );
    }

    return filtered;
});

const selectDisease = (disease) => {
    selectedDisease.value = disease;
};

const editDisease = (disease) => {
    searchText.value = disease.name;
    selectedDisease.value = disease;
};

const addDisease = () => {
    if (searchText.value.trim()) {
        const newDisease = {
            id: Math.max(...diseases.value.map(d => d.id), 0) + 1,
            name: searchText.value.trim(),
            language: selectedLanguage.value
        };
        diseases.value.push(newDisease);
        searchText.value = '';
        selectedDisease.value = newDisease;
    }
};
</script>

<style scoped>
.disease-item {
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.disease-item:hover:not(.bg-primary) {
    background-color: rgba(0, 0, 0, 0.05);
}

.cursor-pointer {
    cursor: pointer;
}
</style>
