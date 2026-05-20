<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Supplier extends Model
{
    use HasFactory, SoftDeletes, HasUuids;

    protected $keyType = 'string';
    protected $primaryKey = 'id';
    public $incrementing = false;

    protected $fillable = [
        'name',
        'description',
        'contact_person',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'website',
        'tax_id',
        'payment_terms',
        'delivery_terms',
        'lead_time_days',
        'minimum_order_value',
        'currency',
        'is_active',
        'is_manufacturer',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'lead_time_days' => 'integer',
        'minimum_order_value' => 'float',
        'is_active' => 'boolean',
        'is_manufacturer' => 'boolean',
    ];

    /**
     * Get the user who created the supplier.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the supplier.
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the parts supplied by this supplier.
     */
    public function parts()
    {
        return $this->hasMany(Part::class, 'supplier_id');
    }

    /**
     * Get the parts manufactured by this supplier.
     */
    public function manufacturedParts()
    {
        return $this->hasMany(Part::class, 'manufacturer_id');
    }

    /**
     * Get the purchase orders from this supplier.
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    /**
     * Scope a query to only include active suppliers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include manufacturers.
     */
    public function scopeManufacturers($query)
    {
        return $query->where('is_manufacturer', true);
    }

    /**
     * Scope a query to only include suppliers (not manufacturers).
     */
    public function scopeSuppliersOnly($query)
    {
        return $query->where('is_manufacturer', false);
    }

    /**
     * Get the full address.
     */
    public function getFullAddressAttribute(): string
    {
        $addressParts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Get contact information.
     */
    public function getContactInfoAttribute(): array
    {
        return [
            'contact_person' => $this->contact_person,
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
        ];
    }

    /**
     * Get business terms.
     */
    public function getBusinessTermsAttribute(): array
    {
        return [
            'payment_terms' => $this->payment_terms,
            'delivery_terms' => $this->delivery_terms,
            'lead_time_days' => $this->lead_time_days,
            'minimum_order_value' => $this->minimum_order_value,
            'currency' => $this->currency,
        ];
    }

    /**
     * Check if supplier is both manufacturer and supplier.
     */
    public function isBothManufacturerAndSupplier(): bool
    {
        return $this->is_manufacturer && $this->parts()->exists();
    }

    /**
     * Get supplier type display.
     */
    public function getSupplierTypeDisplayAttribute(): string
    {
        if ($this->is_manufacturer && $this->parts()->exists()) {
            return 'Manufacturer & Supplier';
        } elseif ($this->is_manufacturer) {
            return 'Manufacturer';
        } else {
            return 'Supplier';
        }
    }
}

