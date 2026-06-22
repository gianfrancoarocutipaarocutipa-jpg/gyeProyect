<?php
declare(strict_types=1);

namespace GemMotors\Services;

/**
 * Servicio para integración con Cloudinary (almacenamiento de fotos y videos)
 * En un sistema real, esto usaría la API de Cloudinary
 * Por ahora, simularemos la integración
 */
class CloudinaryService
{
    // Estas normalmente vendrían de las variables de entorno
    private string $cloudName;
    private string $apiKey;
    private string $apiSecret;

    public function __construct()
    {
        $this->cloudName = $_ENV['CLOUDINARY_CLOUD_NAME'] ?? 'demo';
        $this->apiKey = $_ENV['CLOUDINARY_API_KEY'] ?? 'demo_key';
        $this->apiSecret = $_ENV['CLOUDINARY_API_SECRET'] ?? 'demo_secret';
    }

    /**
     * Sube una imagen o video a Cloudinary
     * @param string $filePath Ruta local del archivo a subir
     * @param string $folder Carpeta en Cloudinary donde almacenar
     * @return array Resultado de la subida con URL y otros metadatos
     */
    public function subirArchivo(string $filePath, string $folder = 'gem_motors/evidencias'): array
    {
        // En un sistema real, aquí se haría la llamada a la API de Cloudinary
        // Por ahora, simulamos la respuesta
        
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException('El archivo no existe: ' . $filePath);
        }
        
        // Obtener extensión del archivo
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // Determinar tipo de medio
        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];
        $videoExtensions = ['mp4', 'avi', 'mov', 'wmv', 'flv', 'mkv'];
        
        $resourceType = in_array($extension, $imageExtensions, true) ? 'image' : 
                       (in_array($extension, $videoExtensions, true) ? 'video' : 'raw');
        
        // Simular ID público de Cloudinary
        $publicId = $folder . '/' . uniqid('evidencia_', true);
        
        // Simular respuesta de Cloudinary
        return [
            'success' => true,
            'public_id' => $publicId,
            'url' => "https://res.cloudinary.com/{$this->cloudName}/{$resourceType}/upload/{$publicId}.{$extension}",
            'secure_url' => "https://res.cloudinary.com/{$this->cloudName}/{$resourceType}/upload/{$publicId}.{$extension}",
            'format' => $extension,
            'resource_type' => $resourceType,
            'created_at' => date('c'),
            'tags' => [],
            'bytes' => filesize($filePath),
            'width' => $resourceType === 'image' ? 800 : null, // Valor simulado
            'height' => $resourceType === 'image' ? 600 : null, // Valor simulado
        ];
    }

    /**
     * Elimina un archivo de Cloudinary
     * @param string $publicId ID público del archivo en Cloudinary
     * @return boolean True si se eliminó correctamente
     */
    public function eliminarArchivo(string $publicId): bool
    {
        // En un sistema real, aquí se haría la llamada a la API de Cloudinary
        // Por ahora, simulamos la eliminación
        
        // En un sistema real, verificaríamos si el archivo existe y luego lo eliminaríamos
        return true;
    }

    /**
     * Obtiene información de un archivo en Cloudinary
     * @param string $publicId ID público del archivo en Cloudinary
     * @return array Información del archivo
     */
    public function obtenerInformacion(string $publicId): array
    {
        // En un sistema real, aquí se haría la llamada a la API de Cloudinary
        // Por ahora, simulamos la respuesta
        
        return [
            'success' => true,
            'public_id' => $publicId,
            'url' => "https://res.cloudinary.com/{$this->cloudName}/image/upload/{$publicId}.jpg",
            'secure_url' => "https://res.cloudinary.com/{$this->cloudName}/image/upload/{$publicId}.jpg",
            'format' => 'jpg',
            'resource_type' => 'image',
            'created_at' => date('c'),
            'bytes' => 102400, // 100KB simulado
            'width' => 800,
            'height' => 600,
        ];
    }
}