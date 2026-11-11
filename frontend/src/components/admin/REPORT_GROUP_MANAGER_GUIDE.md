# Report Group Manager - User Guide

## Overview
The **Report Group Manager** lets you organize test parameters into groups for different report layouts. For example, create a "Lipid Panel" group containing cholesterol, HDL, LDL, and triglycerides parameters.

## How It Works

### Left Panel: Settings & Groups
1. **Create New Group**
   - Select a test **Category** (e.g., "Clinical Chemistry")
   - Enter a **Group Name** (e.g., "Lipid Panel")
   - Optionally select a **Report Layout** (visual design for the printed report)
   - Click **Create Group** button

2. **Existing Groups**
   - Shows all groups for the selected category
   - **Click a group card** to select it and view/edit its parameters
   - Active (selected) group is highlighted in blue
   - Badges show:
     - "Layout assigned" if the group has a print layout
     - "X parameters" — count of assigned parameters
   - **Delete button** removes the group permanently

### Right Panel: Assign Parameters (el-transfer Component)

Once you **select a group**, the right panel shows:

- **Left column:** "Available Parameters" — all parameters for the category
- **Right column:** "Assigned Parameters" — currently assigned to the selected group
- **Buttons in the middle:**
  - "Assign" → moves selected left items to the right
  - "Remove" → moves selected right items back to the left

**Actions:**
- **Search/Filter:** Type in the search box to find parameters by name
- **Select:** Click rows to mark them, then click transfer buttons
- **Automatic save:** Changes are saved to the backend immediately

### Example Workflow

1. Select category: "Clinical Chemistry"
2. Create group: "Lipid Panel"
3. Select "Lipid Panel" from the Existing Groups list
4. In the transfer panel:
   - Filter for "lipid" (shows relevant parameters)
   - Select "Cholesterol", "HDL", "LDL", "Triglycerides" on the left
   - Click "Assign" button → items move to the right
5. The group now contains 4 parameters

---

## Visual Design Changes

### Before (Old UI Problems)
- ❌ Three-panel layout with empty, unreadable rows
- ❌ Fragile drag-and-drop requiring mouse precision
- ❌ Hard to understand which parameters were assigned
- ❌ JSON configuration visible to users

### After (New el-transfer UI Benefits)
- ✅ Clear dual-list interface (Available | Assigned)
- ✅ Keyboard-accessible (Tab, Space, Arrow keys)
- ✅ Built-in search/filter
- ✅ Visual feedback (button states, selection highlighting)
- ✅ Error messages if anything fails
- ✅ Responsive and touch-friendly

---

## API Endpoints Used

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/api/test-categories` | GET | Load all test categories |
| `/api/test-parameters` | GET | Load all test parameters |
| `/api/report-layouts` | GET | Load available report layouts |
| `/api/report-groups` | GET | Load groups for a category |
| `/api/report-groups` | POST | Create a new group |
| `/api/report-groups/{id}` | DELETE | Delete a group |
| `/api/report-groups/assign-parameter/{id}` | PUT | Assign/unassign parameter to/from group |

---

## Troubleshooting

### Category dropdown is empty
- **Cause:** No test categories exist in the database
- **Fix:** Ensure the `test_categories` table has data, or use the seeder to populate it

### Parameters not showing in transfer panel
- **Cause:** No parameters for the selected category
- **Fix:** Check that parameters exist and are linked to the category with `test_category_id`

### Changes not saving
- **Cause:** Backend API error
- **Fix:** Check browser DevTools → Network tab and Console for error messages; verify API responses

### "Select a group to assign parameters" message
- **Cause:** No group is selected
- **Fix:** Click on a group card in the left panel to select it

---

## Keyboard Shortcuts (in transfer panel)
- **Tab:** Navigate between columns and buttons
- **Space:** Select/deselect a row
- **Arrow Keys:** Navigate rows
- **Enter:** Activate button (once focused)
