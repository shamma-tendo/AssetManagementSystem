{{--
    Location Picker Component
    Props: $locations (Collection), $fieldName (string), $oldValue (string), $accentColor ('blue'|'green')
--}}
@php
    $fieldName   = $fieldName   ?? 'location_id';
    $oldValue    = $oldValue    ?? old($fieldName);
    $accentColor = $accentColor ?? 'blue';
    $pickerId    = 'loc-picker-' . uniqid();

    // Resolve display label for old value
    $displayLabel = '';
    if ($oldValue) {
        $found = $locations->firstWhere('id', $oldValue);
        $displayLabel = $found ? $found->name : $oldValue;
    }

    $ugandaDistricts = [
        'Abim','Adjumani','Agago','Alebtong','Amolatar','Amudat','Amuria','Amuru',
        'Apac','Arua','Budaka','Bududa','Bugiri','Bugweri','Buhweju','Buikwe',
        'Bukedea','Bukomansimbi','Bukwo','Bulambuli','Buliisa','Bundibugyo',
        'Bunyangabu','Bushenyi','Busia','Butaleja','Butebo','Buvuma','Buyende',
        'Dokolo','Gulu','Hoima','Ibanda','Iganga','Isingiro','Jinja','Kaabong',
        'Kabale','Kabarole','Kaberamaido','Kagadi','Kakumiro','Kalaki','Kalangala',
        'Kaliro','Kalungu','Kampala','Kamuli','Kamwenge','Kanungu','Kapchorwa',
        'Kapelebyong','Karenga','Kasanda','Kasese','Katakwi','Kayunga','Kazo',
        'Kibaale','Kiboga','Kibuku','Kikuube','Kiruhura','Kiryandongo','Kisoro',
        'Kitgum','Koboko','Kole','Kotido','Kumi','Kwania','Kween','Kyankwanzi',
        'Kyegegwa','Kyenjojo','Kyotera','Lamwo','Lira','Luuka','Luwero','Lwengo',
        'Lyantonde','Madi-Okollo','Manafwa','Maracha','Masaka','Masindi','Mayuge',
        'Mbale','Mbarara','Mitooma','Mityana','Moroto','Moyo','Mpigi','Mubende',
        'Mukono','Nabilatuk','Nakapiripirit','Nakaseke','Nakasongola','Namayingo',
        'Namisindwa','Namutumba','Napak','Nebbi','Ngora','Ntoroko','Ntungamo',
        'Nwoya','Obongi','Omoro','Otuke','Oyam','Pader','Pakwach','Pallisa',
        'Rakai','Rubanda','Rubirizi','Rukiga','Rukungiri','Rwampara','Sembabule',
        'Serere','Sheema','Sironko','Soroti','Tororo','Wakiso','Yumbe','Zombo',
    ];
@endphp

<div class="relative" id="{{ $pickerId }}-wrap">
    <input type="text"
        id="{{ $pickerId }}"
        value="{{ $displayLabel }}"
        placeholder="Type district or location..."
        autocomplete="off"
        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:outline-none focus:ring-{{ $accentColor }}-500 focus:border-{{ $accentColor }}-500">

    <input type="hidden" name="{{ $fieldName }}" id="{{ $pickerId }}-val" value="{{ $oldValue }}">

    <ul id="{{ $pickerId }}-list"
        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-xl max-h-56 overflow-y-auto text-sm hidden"></ul>
</div>
<p class="mt-1 text-xs text-gray-400">📍 Type to search Uganda districts or saved locations</p>

<script>
(function() {
    const dbOptions = @json($locations->map(fn($l) => ['id' => $l->id, 'name' => $l->name, 'saved' => true])->values());
    const districts = @json($ugandaDistricts);

    const allOptions = [
        ...dbOptions,
        ...districts
            .filter(d => !dbOptions.some(o => o.name.toLowerCase() === (d + ', Uganda').toLowerCase() || o.name.toLowerCase() === d.toLowerCase()))
            .map(d => ({ id: null, name: d + ', Uganda', saved: false }))
    ];

    const input  = document.getElementById('{{ $pickerId }}');
    const hidden = document.getElementById('{{ $pickerId }}-val');
    const list   = document.getElementById('{{ $pickerId }}-list');

    function render(results) {
        list.innerHTML = '';
        if (!results.length) { list.classList.add('hidden'); return; }
        results.slice(0, 10).forEach(opt => {
            const li = document.createElement('li');
            li.className = 'px-4 py-2 hover:bg-{{ $accentColor === "green" ? "green" : "blue" }}-50 cursor-pointer flex items-center gap-2';
            li.innerHTML = `
                <span class="w-2 h-2 rounded-full flex-shrink-0 ${opt.saved ? 'bg-blue-500' : 'bg-gray-300'}"></span>
                <span>${opt.name}</span>
                ${opt.saved ? '<span class="ml-auto text-xs text-blue-400">saved</span>' : ''}
            `;
            li.addEventListener('mousedown', e => {
                e.preventDefault();
                input.value  = opt.name;
                hidden.value = opt.id || opt.name;
                list.classList.add('hidden');
            });
            list.appendChild(li);
        });
        list.classList.remove('hidden');
    }

    input.addEventListener('input', () => {
        const q = input.value.toLowerCase().trim();
        hidden.value = input.value; // keep as free text until selected
        if (!q) { list.classList.add('hidden'); return; }
        render(allOptions.filter(o => o.name.toLowerCase().includes(q)));
    });

    input.addEventListener('focus', () => {
        if (input.value.length > 0) input.dispatchEvent(new Event('input'));
    });

    document.addEventListener('click', e => {
        if (!document.getElementById('{{ $pickerId }}-wrap').contains(e.target)) {
            list.classList.add('hidden');
        }
    });
})();
</script>
