import { Component, Input, Output, EventEmitter, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { MatCheckboxModule } from '@angular/material/checkbox';
import { MatCardModule } from '@angular/material/card';
import { MatButtonModule } from '@angular/material/button';
import { TranslatePipe, TranslateService } from '@ngx-translate/core';
import { MatTooltip } from '@angular/material/tooltip';
import { MassUtil } from '../../util/mass-util';
import { MassTitleCategory } from '../../enum/mass-title-category';
import { MassTitleCategoryConfig } from '../../util/mass-title-category-config';

@Component({
  selector: 'app-mass-title-filter',
  standalone: true,
  imports: [
    CommonModule,
    FormsModule,
    MatCheckboxModule,
    MatCardModule,
    MatButtonModule,
    TranslatePipe,
    MatTooltip
  ],
  templateUrl: './mass-title-filter.component.html',
  styleUrls: ['./mass-title-filter.component.css']
})
export class MassTitleFilterComponent implements OnInit {
  @Input() activeCategories: Set<MassTitleCategory> = new Set();
  @Output() categoriesChanged = new EventEmitter<Set<MassTitleCategory>>();

  categories: MassTitleCategory[] = [];
  categoryColors: Record<MassTitleCategory, string> = {} as any;

  constructor(private translateService: TranslateService) {}

  ngOnInit(): void {
    this.categories = MassUtil.getAllCategories();
    
    // Szín config beállítása
    this.categories.forEach(category => {
      this.categoryColors[category] = MassTitleCategoryConfig.getColorByCategory(category);
    });

    // Ha üres, alapértelmezésben mindegyik kijelölt
    if (this.activeCategories.size === 0) {
      this.categories.forEach(category => {
        this.activeCategories.add(category);
      });
    }
  }

  toggleCategory(category: MassTitleCategory): void {
    if (this.activeCategories.has(category)) {
      this.activeCategories.delete(category);
    } else {
      this.activeCategories.add(category);
    }
    this.categoriesChanged.emit(new Set(this.activeCategories));
  }

  isChecked(category: MassTitleCategory): boolean {
    return this.activeCategories.has(category);
  }

  selectAll(): void {
    this.categories.forEach(category => {
      this.activeCategories.add(category);
    });
    this.categoriesChanged.emit(new Set(this.activeCategories));
  }

  deselectAll(): void {
    this.activeCategories.clear();
    this.categoriesChanged.emit(new Set(this.activeCategories));
  }

  getCategoryLabel(category: MassTitleCategory): string {
    return `MASS_TITLE_CATEGORY.${category}`;
  }
}
