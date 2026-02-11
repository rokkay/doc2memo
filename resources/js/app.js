import './bootstrap';

const THEME_KEY = 'theme'

const getPreferredTheme = () => {
    const storedTheme = localStorage.getItem(THEME_KEY)

    if (storedTheme === 'light' || storedTheme === 'dark') {
        return storedTheme
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
}

const applyTheme = (theme) => {
    document.documentElement.classList.toggle('dark', theme === 'dark')
}

const updateThemeIcon = () => {
    const sunIcon = document.getElementById('theme-toggle-sun')
    const moonIcon = document.getElementById('theme-toggle-moon')
    const isDark = document.documentElement.classList.contains('dark')

    if (!sunIcon || !moonIcon) {
        return
    }

    sunIcon.classList.toggle('hidden', !isDark)
    moonIcon.classList.toggle('hidden', isDark)
}

const syncTheme = () => {
    applyTheme(getPreferredTheme())
    updateThemeIcon()
}

const mountThemeToggle = () => {
    const toggleButton = document.getElementById('theme-toggle')

    if (!toggleButton || toggleButton.dataset.boundThemeToggle === 'true') {
        updateThemeIcon()

        return
    }

    toggleButton.addEventListener('click', () => {
        const isDark = document.documentElement.classList.contains('dark')
        const nextTheme = isDark ? 'light' : 'dark'

        localStorage.setItem(THEME_KEY, nextTheme)
        applyTheme(nextTheme)
        updateThemeIcon()
    })

    toggleButton.dataset.boundThemeToggle = 'true'
    updateThemeIcon()
}

syncTheme()

document.addEventListener('DOMContentLoaded', mountThemeToggle)
document.addEventListener('livewire:navigated', mountThemeToggle)
