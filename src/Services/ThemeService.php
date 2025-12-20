<?php

namespace AdultDate\FilamentWirechat\Services;

use Filament\Facades\Filament;
use Filament\Support\Colors\Color;

class ThemeService
{
    /**
     * Render the theme CSS variables as a style tag.
     * Uses Filament's panel colors by default, with config overrides.
     */
    public function renderStyles(): string
    {
        $config = config('filament-wirechat.theme', []);
        
        // Get Filament panel colors (if available)
        $panel = Filament::getCurrentPanel();
        $panelColors = $panel?->getColors() ?? [];
        
        // Get primary color from Filament panel or config
        $brandPrimary = $this->getBrandPrimary($panelColors, $config);
        
        // Get Filament's default gray colors for backgrounds
        $filamentGray = Color::Gray;
        
        // Get light mode colors - use config or Filament defaults
        // Light primary should be white, not gray
        $lightPrimary = $config['light_primary'] ?? '#ffffff'; // white
        $lightSecondary = $config['light_secondary'] ?? $filamentGray[100]; // gray-100
        $lightAccent = $config['light_accent'] ?? $filamentGray[200]; // gray-200
        $lightBorder = $config['light_border'] ?? $filamentGray[200]; // gray-200
        
        // Get dark mode colors - use config or Filament defaults
        $darkPrimary = $config['dark_primary'] ?? $filamentGray[950]; // gray-950
        $darkSecondary = $config['dark_secondary'] ?? $filamentGray[900]; // gray-900
        $darkAccent = $config['dark_accent'] ?? $filamentGray[800]; // gray-800
        $darkBorder = $config['dark_border'] ?? $filamentGray[700]; // gray-700
        
        // Build CSS variables
        $css = ":root {\n";
        
        // Brand primary
        $css .= "    --wc-brand-primary: {$brandPrimary};\n";
        
        // Light mode
        $css .= "    --wc-light-primary: {$lightPrimary};\n";
        $css .= "    --wc-light-secondary: {$lightSecondary};\n";
        $css .= "    --wc-light-accent: {$lightAccent};\n";
        $css .= "    --wc-light-border: {$lightBorder};\n";
        
        $css .= "}\n\n";
        
        $css .= ".dark {\n";
        
        // Dark mode
        $css .= "    --wc-dark-primary: {$darkPrimary};\n";
        $css .= "    --wc-dark-secondary: {$darkSecondary};\n";
        $css .= "    --wc-dark-accent: {$darkAccent};\n";
        $css .= "    --wc-dark-border: {$darkBorder};\n";
        $css .= "    --wc-brand-primary: {$brandPrimary};\n";
        
        $css .= "}\n";
        
        return "<style>{$css}</style>";
    }
    
    /**
     * Get the brand primary color from Filament panel or config.
     */
    protected function getBrandPrimary(array $panelColors, array $config): string
    {
        // Use config override if provided
        if (!empty($config['brand_primary'])) {
            return $config['brand_primary'];
        }
        
        // Try to get from Filament panel
        if (!empty($panelColors['primary'])) {
            $primary = $panelColors['primary'];
            
            // If it's an array (Color constant), get the 500 shade
            if (is_array($primary) && isset($primary[500])) {
                return $primary[500];
            }
            
            // If it's a string, use it directly
            if (is_string($primary)) {
                return $primary;
            }
        }
        
        // Default fallback to Blue-500
        return Color::Blue[500];
    }
    
}
