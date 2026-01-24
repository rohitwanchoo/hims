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
                        placeholder="Type dose time instruction"
                        @keyup.enter="addDoseTime"
                    />
                    <button class="btn btn-primary" @click="addDoseTime">
                        Add New
                    </button>
                </div>
            </div>
        </div>

        <!-- Dose Time List -->
        <div class="border rounded p-3" style="height: 450px; overflow-y: auto;">
            <div
                v-for="doseTime in filteredDoseTimes"
                :key="doseTime.id"
                class="dose-time-item py-1 px-2 mb-1 cursor-pointer"
                :class="{ 'bg-primary text-white': selectedDoseTime?.id === doseTime.id }"
                @click="selectDoseTime(doseTime)"
                @dblclick="editDoseTime(doseTime)"
            >
                {{ doseTime.text }}
            </div>
            <div v-if="filteredDoseTimes.length === 0" class="text-center text-muted py-4">
                No dose time instructions found
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const selectedLanguage = ref('marathi');
const searchText = ref('');
const selectedDoseTime = ref(null);

const doseTimes = ref([
    { id: 1, text: 'डोक्यात १० थेंब लावणे   रोज टुपोरो', language: 'marathi' },
    { id: 2, text: '0 - 0 - 1  रात्रि दिवसांनी', language: 'marathi' },
    { id: 3, text: '0 - 0 - 1 रोज रात्री', language: 'marathi' },
    { id: 4, text: '0 - 0 - 1 रोज रात्रि नाक्वावर दिवसा', language: 'marathi' },
    { id: 5, text: '0—1—0 रोज टुपारी', language: 'marathi' },
    { id: 6, text: '0—1—0 रोज टुपारी ऐकुनर शनिवार', language: 'marathi' },
    { id: 7, text: '1 -  0 - 1 ऐकुनर शनिवार', language: 'marathi' },
    { id: 8, text: '1 -  1 - 1 ऐकुनर शनिवार', language: 'marathi' },
    { id: 9, text: '1 - 0 - 0 शनिवार व रविवार सकारी १ टुपारीवार', language: 'marathi' },
    { id: 10, text: '1 - 0 - 1 वेषण्यापोषी', language: 'marathi' },
    { id: 11, text: '1 - 0 - 1 वेषण्यानंतर', language: 'marathi' },
    { id: 12, text: '1 ML TWICE DAILY  APPLY AND MASSAGE GENTLY TO...', language: 'english' },
    { id: 13, text: '1-0-0 सकाळी', language: 'marathi' },
    { id: 14, text: '1-0-1 twice daily', language: 'english' },
    { id: 15, text: '1—0——1 रोज सकाळी ऐणमाळी नावने', language: 'marathi' },
    { id: 16, text: '1——0——1 रोज सकाळी ऐणमाळी नावने', language: 'marathi' },
    { id: 17, text: '1——1——1 प्रियात्मन वोट फेशन', language: 'marathi' },
    { id: 18, text: '0 - 1 - 0', language: 'english' },
    { id: 19, text: '0 - 1- 0', language: 'english' },
    { id: 20, text: '0- 1- 0', language: 'english' }
]);

const filteredDoseTimes = computed(() => {
    let filtered = doseTimes.value.filter(d => d.language === selectedLanguage.value);

    if (searchText.value.trim()) {
        const search = searchText.value.toLowerCase();
        filtered = filtered.filter(d =>
            d.text.toLowerCase().includes(search)
        );
    }

    return filtered;
});

const selectDoseTime = (doseTime) => {
    selectedDoseTime.value = doseTime;
};

const editDoseTime = (doseTime) => {
    searchText.value = doseTime.text;
    selectedDoseTime.value = doseTime;
};

const addDoseTime = () => {
    if (searchText.value.trim()) {
        const newDoseTime = {
            id: Math.max(...doseTimes.value.map(d => d.id), 0) + 1,
            text: searchText.value.trim(),
            language: selectedLanguage.value
        };
        doseTimes.value.push(newDoseTime);
        searchText.value = '';
        selectedDoseTime.value = newDoseTime;
    }
};
</script>

<style scoped>
.dose-time-item {
    cursor: pointer;
    border-radius: 4px;
    transition: background-color 0.2s;
}

.dose-time-item:hover:not(.bg-primary) {
    background-color: rgba(0, 0, 0, 0.05);
}

.cursor-pointer {
    cursor: pointer;
}
</style>
