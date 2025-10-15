using System.Collections.Generic;

namespace Scoreboard.Models.Entities
{
    public class RoleMenu
    {
        public int Id { get; set; }
        public int RoleId { get; set; }
        public Role? Role { get; set; }

        public int MenuId { get; set; }
        public Menu? Menu { get; set; }

        public int? RoleMenuId { get; set; }
        public RoleMenu? Parent { get; set; }
        public ICollection<RoleMenu> RoleMenus { get; set; } = new List<RoleMenu>();
    }
}
